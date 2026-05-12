<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Services\AuditLogService;
use App\Services\StockMutationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
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
        ];

        $purchaseReturns = PurchaseReturn::query()
            ->with(['purchase:id,invoice_number', 'supplier:id,name', 'creator:id,name', 'completer:id,name'])
            ->when($filters['search'], function ($query, $search) {
                $query->where('return_number', 'like', '%' . $search . '%')
                      ->orWhereHas('purchase', fn ($q) => $q->where('invoice_number', 'like', '%' . $search . '%'))
                      ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
            })
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Dashboard/PurchaseReturns/Index', [
            'purchaseReturns' => $purchaseReturns,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $search = $request->input('search', '');

        $purchases = blank($search)
            ? collect()
            : Purchase::query()
                ->with(['supplier:id,name'])
                ->where('status', 'finalized')
                ->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', '%' . $search . '%')
                          ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                })
                ->withCount('items')
                ->latest()
                ->limit(10)
                ->get();

        return Inertia::render('Dashboard/PurchaseReturns/Create', [
            'purchases' => $purchases,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
        ]);

        $purchase = Purchase::with('items.product')->findOrFail($validated['purchase_id']);

        if ($purchase->status !== 'finalized') {
            throw ValidationException::withMessages([
                'purchase_id' => 'Hanya pembelian yang sudah di-finalisasi yang bisa diretur.',
            ]);
        }

        if ($purchase->items->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_id' => 'Pembelian ini tidak memiliki item.',
            ]);
        }

        $purchaseReturn = DB::transaction(function () use ($request, $purchase) {
            $purchaseReturn = PurchaseReturn::create([
                'return_number' => $this->generateReturnNumber(),
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'status' => 'draft',
                'total_return_amount' => 0,
                'refund_amount' => 0,
                'credited_amount' => 0,
                'created_by' => $request->user()?->id,
            ]);

            // Pre-populate items from purchase
            foreach ($purchase->items as $item) {
                // Calculate how many items from this purchase_item have already been returned
                $alreadyReturned = PurchaseReturnItem::where('purchase_item_id', $item->id)
                    ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'completed'))
                    ->sum('qty_return');

                $remaining = max(0, $item->qty - $alreadyReturned);

                if ($remaining > 0) {
                    $purchaseReturn->items()->create([
                        'purchase_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'qty_return' => 0,
                        'buy_price' => $item->buy_price,
                        'subtotal' => 0,
                        'return_reason' => null,
                    ]);
                }
            }

            return $purchaseReturn;
        });

        return to_route('purchase-returns.show', $purchaseReturn);
    }

    public function show(PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load([
            'purchase:id,invoice_number,grand_total',
            'supplier:id,name,phone,address',
            'creator:id,name',
            'completer:id,name',
            'items.product.category:id,name',
            'items.purchaseItem',
        ]);

        // Calculate remaining returnable qty for each item
        $purchaseReturn->items->each(function ($item) use ($purchaseReturn) {
            $alreadyReturned = PurchaseReturnItem::where('purchase_item_id', $item->purchase_item_id)
                ->where('purchase_return_id', '!=', $purchaseReturn->id)
                ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'completed'))
                ->sum('qty_return');

            $item->max_returnable = max(0, ($item->purchaseItem->qty ?? 0) - $alreadyReturned);
            $item->original_qty = $item->purchaseItem->qty ?? 0;
        });

        // Check if a payable exists for this purchase
        $payable = Payable::where('document_number', $purchaseReturn->purchase->invoice_number)->first();

        return Inertia::render('Dashboard/PurchaseReturns/Show', [
            'purchaseReturn' => $purchaseReturn,
            'payable' => $payable ? [
                'id' => $payable->id,
                'remaining' => $payable->remaining,
                'status' => $payable->status,
            ] : null,
        ]);
    }

    public function updateItem(Request $request, PurchaseReturn $purchaseReturn, PurchaseReturnItem $item): RedirectResponse
    {
        $this->ensureDraft($purchaseReturn);
        $this->ensureItemBelongsToReturn($purchaseReturn, $item);

        $validated = $request->validate([
            'qty_return' => 'required|integer|min:0',
            'return_reason' => 'nullable|string|max:500',
        ]);

        $qtyReturn = $validated['qty_return'];

        // Calculate max returnable
        $alreadyReturned = PurchaseReturnItem::where('purchase_item_id', $item->purchase_item_id)
            ->where('id', '!=', $item->id)
            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'completed'))
            ->sum('qty_return');

        $maxReturnable = max(0, ($item->purchaseItem->qty ?? 0) - $alreadyReturned);

        if ($qtyReturn > $maxReturnable) {
            throw ValidationException::withMessages([
                'qty_return' => "Qty retur tidak boleh melebihi sisa yang bisa diretur ({$maxReturnable}).",
            ]);
        }

        $subtotal = $qtyReturn * $item->buy_price;

        $item->update([
            'qty_return' => $qtyReturn,
            'subtotal' => $subtotal,
            'return_reason' => $validated['return_reason'] ?? $item->return_reason,
        ]);

        $this->recalculateTotals($purchaseReturn);

        return back()->with('success', 'Item retur diperbarui.');
    }

    public function complete(Request $request, PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->ensureDraft($purchaseReturn);

        $validated = $request->validate([
            'resolution_type' => 'required|in:refund,credit,exchange',
            'notes' => 'nullable|string|max:1000',
        ]);

        $purchaseReturn->load([
            'items.product',
            'items.purchaseItem',
            'purchase',
        ]);

        $itemsWithQty = $purchaseReturn->items->filter(fn ($item) => $item->qty_return > 0);

        if ($itemsWithQty->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_return' => 'Setidaknya harus ada satu item dengan qty retur > 0.',
            ]);
        }

        // Validate each item has a reason
        foreach ($itemsWithQty as $item) {
            if (blank($item->return_reason)) {
                throw ValidationException::withMessages([
                    'purchase_return' => 'Semua item yang diretur harus memiliki alasan retur.',
                ]);
            }
        }

        // Validate qty doesn't exceed remaining
        foreach ($itemsWithQty as $item) {
            $alreadyReturned = PurchaseReturnItem::where('purchase_item_id', $item->purchase_item_id)
                ->where('id', '!=', $item->id)
                ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'completed'))
                ->sum('qty_return');

            $maxReturnable = max(0, ($item->purchaseItem->qty ?? 0) - $alreadyReturned);

            if ($item->qty_return > $maxReturnable) {
                throw ValidationException::withMessages([
                    'purchase_return' => "Item {$item->product->title} qty retur melebihi sisa yang bisa diretur.",
                ]);
            }
        }

        $resolutionType = $validated['resolution_type'];
        $totalReturnAmount = (int) $itemsWithQty->sum('subtotal');

        DB::transaction(function () use ($request, $purchaseReturn, $itemsWithQty, $resolutionType, $totalReturnAmount, $validated) {
            // 1. Adjust stock (for refund & credit: stock goes out; for exchange: stock stays same)
            if ($resolutionType !== 'exchange') {
                foreach ($itemsWithQty as $item) {
                    $product = $item->product()->lockForUpdate()->first();

                    if (! $product) {
                        continue;
                    }

                    $stockBefore = (int) $product->stock;
                    $stockAfter = max(0, $stockBefore - $item->qty_return);

                    $product->update(['stock' => $stockAfter]);

                    $this->stockMutationService->recordPurchaseReturn(
                        product: $product,
                        purchaseReturn: $purchaseReturn,
                        stockBefore: $stockBefore,
                        stockAfter: $stockAfter,
                        reason: "Retur pembelian ({$purchaseReturn->return_number}): {$item->return_reason}",
                        userId: $request->user()?->id,
                    );
                }
            }
            // For exchange: record a single exchange mutation (net zero, stock unchanged)
            if ($resolutionType === 'exchange') {
                foreach ($itemsWithQty as $item) {
                    $product = $item->product()->lockForUpdate()->first();

                    if (! $product) {
                        continue;
                    }

                    $stockBefore = (int) $product->stock;

                    \App\Models\StockMutation::create([
                        'product_id' => $product->id,
                        'reference_type' => 'purchase_return',
                        'reference_id' => $purchaseReturn->id,
                        'mutation_type' => 'adjustment',
                        'qty' => $item->qty_return,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore, // net zero — stock unchanged
                        'notes' => "Tukar barang dengan supplier ({$purchaseReturn->return_number}): {$item->qty_return} unit diretur dan ditukar barang baru. Alasan: {$item->return_reason}",
                        'created_by' => $request->user()?->id,
                    ]);
                    // Stock stays the same, no product update needed
                }
            }

            // 2. Handle financial settlement
            $refundAmount = 0;
            $creditedAmount = 0;

            if ($resolutionType === 'credit') {
                // Try to deduct from existing payable
                $payable = Payable::where('document_number', $purchaseReturn->purchase->invoice_number)
                    ->where('status', '!=', 'paid')
                    ->lockForUpdate()
                    ->first();

                if ($payable) {
                    $remaining = $payable->remaining;
                    $creditedAmount = min($totalReturnAmount, $remaining);

                    $newPaid = $payable->paid + $creditedAmount;
                    $payable->update([
                        'paid' => $newPaid,
                        'status' => $newPaid >= $payable->total ? 'paid' : $payable->status,
                        'note' => $payable->note . "\nDipotong retur {$purchaseReturn->return_number}: " . number_format($creditedAmount, 0, ',', '.'),
                    ]);

                    // If return amount exceeds remaining payable, the rest is refund
                    $refundAmount = max(0, $totalReturnAmount - $creditedAmount);
                } else {
                    // No payable found, treat entire amount as refund
                    $refundAmount = $totalReturnAmount;
                }
            } elseif ($resolutionType === 'refund') {
                $refundAmount = $totalReturnAmount;
            }
            // exchange: no financial impact

            // 3. Update the return record
            $purchaseReturn->update([
                'resolution_type' => $resolutionType,
                'status' => 'completed',
                'total_return_amount' => $totalReturnAmount,
                'refund_amount' => $refundAmount,
                'credited_amount' => $creditedAmount,
                'notes' => $validated['notes'] ?? $purchaseReturn->notes,
                'completed_by' => $request->user()?->id,
                'completed_at' => now(),
            ]);
        });

        $this->auditLogService->log(
            event: 'purchase_return.completed',
            module: 'purchase-returns',
            auditable: $purchaseReturn,
            description: "Retur pembelian diselesaikan ({$resolutionType}).",
            before: ['status' => 'draft'],
            after: [
                'status' => 'completed',
                'resolution_type' => $resolutionType,
                'total_return_amount' => $totalReturnAmount,
            ],
        );

        return back()->with('success', 'Retur pembelian berhasil diselesaikan.');
    }

    private function ensureDraft(PurchaseReturn $purchaseReturn): void
    {
        if (! $purchaseReturn->isDraft()) {
            throw ValidationException::withMessages([
                'purchase_return' => 'Retur pembelian yang sudah selesai tidak dapat diubah.',
            ]);
        }
    }

    private function ensureItemBelongsToReturn(PurchaseReturn $purchaseReturn, PurchaseReturnItem $item): void
    {
        if ($item->purchase_return_id !== $purchaseReturn->id) {
            abort(404);
        }
    }

    private function recalculateTotals(PurchaseReturn $purchaseReturn): void
    {
        $total = $purchaseReturn->items()->sum('subtotal');

        $purchaseReturn->update([
            'total_return_amount' => $total,
        ]);
    }

    private function generateReturnNumber(): string
    {
        do {
            $code = 'RTN-PUR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (PurchaseReturn::where('return_number', $code)->exists());

        return $code;
    }
}
