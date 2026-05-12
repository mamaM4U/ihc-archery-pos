<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\StockMutationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly StockMutationService $stockMutationService,
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'creator:id,name', 'finalizer:id,name'])
            ->when($filters['search'], function ($query, $search) {
                $query->where('invoice_number', 'like', '%' . $search . '%')
                      ->orWhereHas('supplier', function ($q) use ($search) {
                          $q->where('name', 'like', '%' . $search . '%');
                      });
            })
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Dashboard/Purchases/Index', [
            'purchases' => $purchases,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        
        return Inertia::render('Dashboard/Purchases/Create', [
            'suppliers' => $suppliers
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $purchase = Purchase::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'supplier_id' => $validated['supplier_id'],
            'notes' => $validated['notes'],
            'status' => 'draft',
            'created_by' => $request->user()?->id,
            'total' => 0,
            'discount' => 0,
            'grand_total' => 0,
        ]);

        return to_route('purchases.show', $purchase);
    }

    public function show(Request $request, Purchase $purchase): Response
    {
        $purchase->load([
            'supplier:id,name,phone,address',
            'creator:id,name',
            'finalizer:id,name',
            'items.product.category:id,name',
        ]);

        $productFilters = [
            'search' => $request->input('product_search', ''),
        ];

        $selectedProductIds = $purchase->items->pluck('product_id');

        $availableProducts = blank($productFilters['search'])
            ? collect()
            : Product::query()
                ->with('category:id,name')
                ->where(function ($builder) use ($productFilters) {
                    $builder
                        ->where('title', 'like', '%' . $productFilters['search'] . '%')
                        ->orWhere('barcode', 'like', '%' . $productFilters['search'] . '%')
                        ->orWhere('sku', 'like', '%' . $productFilters['search'] . '%');
                })
                ->whereNotIn('id', $selectedProductIds)
                ->orderBy('title')
                ->limit(20)
                ->get();

        return Inertia::render('Dashboard/Purchases/Show', [
            'purchase' => $purchase,
            'availableProducts' => $availableProducts,
            'productFilters' => $productFilters,
        ]);
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->ensureDraft($purchase);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $discount = $validated['discount'] ?? 0;
        $total = $purchase->items()->sum('subtotal');
        $grandTotal = max(0, $total - $discount);

        $purchase->update([
            'notes' => $validated['notes'] ?? $purchase->notes,
            'discount' => $discount,
            'total' => $total,
            'grand_total' => $grandTotal,
        ]);

        return back()->with('success', 'Data pembelian berhasil diperbarui.');
    }

    public function storeItem(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->ensureDraft($purchase);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($purchase->items()->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk sudah ada di nota pembelian ini.',
            ]);
        }

        $purchase->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'buy_price' => $product->buy_price,
            'subtotal' => $product->buy_price,
        ]);

        $this->recalculateTotals($purchase);

        return back()->with('success', 'Produk ditambahkan ke pembelian.');
    }

    public function updateItem(Request $request, Purchase $purchase, PurchaseItem $item): RedirectResponse
    {
        $this->ensureDraft($purchase);
        $this->ensureItemBelongsToPurchase($purchase, $item);

        $validated = $request->validate([
            'qty' => 'required|integer|min:1',
            'buy_price' => 'required|numeric|min:0',
        ]);

        $qty = $validated['qty'];
        $buyPrice = $validated['buy_price'];
        $subtotal = $qty * $buyPrice;

        $item->update([
            'qty' => $qty,
            'buy_price' => $buyPrice,
            'subtotal' => $subtotal,
        ]);

        $this->recalculateTotals($purchase);

        return back()->with('success', 'Item berhasil diperbarui.');
    }

    public function destroyItem(Purchase $purchase, PurchaseItem $item): RedirectResponse
    {
        $this->ensureDraft($purchase);
        $this->ensureItemBelongsToPurchase($purchase, $item);

        $item->delete();
        $this->recalculateTotals($purchase);

        return back()->with('success', 'Item berhasil dihapus.');
    }

    public function finalize(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->ensureDraft($purchase);

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $paidAmount = $validated['paid_amount'];

        if ($purchase->items()->count() === 0) {
            throw ValidationException::withMessages([
                'finalize' => 'Tidak dapat menyelesaikan pembelian tanpa item.',
            ]);
        }

        $purchase->load('items.product');

        DB::transaction(function () use ($request, $purchase, $paidAmount) {
            foreach ($purchase->items as $item) {
                $product = $item->product()->lockForUpdate()->first();
                
                if (! $product) {
                    continue;
                }

                $stockBefore = (int) $product->stock;
                $stockAfter = $stockBefore + $item->qty;

                // Update stock and buy price if changed
                $product->update([
                    'stock' => $stockAfter,
                    'buy_price' => $item->buy_price,
                ]);

                // Record mutation
                StockMutation::create([
                    'product_id' => $product->id,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'mutation_type' => 'in',
                    'qty' => $item->qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => 'Pembelian dari supplier ' . $purchase->supplier->name . ' (Inv: ' . $purchase->invoice_number . ')',
                    'created_by' => $request->user()?->id,
                ]);
            }

            $purchase->update([
                'status' => 'finalized',
                'finalized_by' => $request->user()?->id,
                'finalized_at' => now(),
            ]);

            // Create Payable if not fully paid
            if ($paidAmount < $purchase->grand_total) {
                Payable::create([
                    'supplier_id' => $purchase->supplier_id,
                    'document_number' => $purchase->invoice_number,
                    'total' => $purchase->grand_total,
                    'paid' => $paidAmount,
                    'due_date' => now()->addDays(30)->format('Y-m-d'), // Default 30 days terms
                    'status' => 'unpaid',
                    'note' => 'Hutang pembelian ' . $purchase->invoice_number,
                ]);
            }
        });

        $this->auditLogService->log(
            event: 'purchase.finalized',
            module: 'purchases',
            auditable: $purchase,
            description: 'Pembelian difinalisasi.',
            before: ['status' => 'draft'],
            after: ['status' => 'finalized'],
        );

        return back()->with('success', 'Pembelian berhasil diselesaikan.');
    }

    private function ensureDraft(Purchase $purchase): void
    {
        if (! $purchase->isDraft()) {
            throw ValidationException::withMessages([
                'purchase' => 'Sesi pembelian yang sudah final tidak dapat diubah.',
            ]);
        }
    }

    private function ensureItemBelongsToPurchase(Purchase $purchase, PurchaseItem $item): void
    {
        if ($item->purchase_id !== $purchase->id) {
            abort(404);
        }
    }

    private function recalculateTotals(Purchase $purchase): void
    {
        $total = $purchase->items()->sum('subtotal');
        $grandTotal = max(0, $total - $purchase->discount);

        $purchase->update([
            'total' => $total,
            'grand_total' => $grandTotal,
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $code = 'INV-PUR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Purchase::where('invoice_number', $code)->exists());

        return $code;
    }
}
