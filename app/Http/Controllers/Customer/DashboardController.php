<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = Auth::guard('customer')->user();

        $recentTransactions = $customer->transactions()
            ->with('details.product:id,title')
            ->withCount('details')
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'total_transactions' => $customer->transactions()->count(),
            'total_spent' => (int) $customer->transactions()->sum('grand_total'),
        ];

        return Inertia::render('Customer/Dashboard', [
            'recentTransactions' => $recentTransactions,
            'stats' => $stats,
        ]);
    }
}
