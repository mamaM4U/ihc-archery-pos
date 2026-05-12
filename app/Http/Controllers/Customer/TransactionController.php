<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();

        $filters = [
            'invoice' => $request->input('invoice'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $transactions = $customer->transactions()
            ->with(['cashier:id,name', 'details.product:id,title'])
            ->withCount('details')
            ->when($filters['invoice'], fn ($q, $v) => $q->where('invoice', 'like', "%{$v}%"))
            ->when($filters['start_date'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['end_date'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Customer/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
        ]);
    }

    public function show(string $invoice): Response
    {
        $customer = Auth::guard('customer')->user();

        $transaction = Transaction::where('customer_id', $customer->id)
            ->where('invoice', $invoice)
            ->with([
                'cashier:id,name',
                'details.product:id,title,barcode,sku',
                'details.product.category:id,name',
                'customer:id,name,no_telp,address',
                'bankAccount:id,bank_name,account_number,account_holder',
            ])
            ->firstOrFail();

        return Inertia::render('Customer/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }
}
