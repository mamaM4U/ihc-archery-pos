<?php

namespace App\Http\Middleware;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Payable;
use App\Services\CashierShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $lowStockNotifications    = [];
        $receivableNotifications  = [];
        $payableNotifications     = [];
        $activeCashierShift       = null;
        $customerAuth             = null;

        // Detect if we're in the customer portal
        $isCustomerPortal = $request->is('customer/*') || $request->is('customer');
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();

        if ($isCustomerPortal && $customer) {
            $customerAuth = [
                'id' => $customer->id,
                'name' => $customer->name,
                'no_telp' => $customer->no_telp,
                'address' => $customer->address,
            ];
        }

        // Only run staff queries for non-customer routes
        if (! $isCustomerPortal && $request->user()) {
            $userId = $request->user()->id;
            $lowStockNotifications = Product::where('stock', '<=', 0)
                ->whereNotExists(function ($query) use ($userId) {
                    $query->selectRaw(1)
                        ->from('product_notification_reads as pr')
                        ->whereColumn('pr.product_id', 'products.id')
                        ->where('pr.user_id', $userId)
                        // Only hide if the notification was read after the last product update
                        ->whereColumn('pr.updated_at', '>=', 'products.updated_at');
                })
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(['id', 'title', 'stock', 'updated_at'])
                ->map(function ($product) {
                    return [
                        'id'    => $product->id,
                        'title' => $product->title,
                        'stock' => (int) $product->stock,
                        'time'  => optional($product->updated_at)->diffForHumans(),
                    ];
                });

            $receivableNotifications = Receivable::whereNot('status', 'paid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', now()->addDays(3))
                ->orderBy('due_date')
                ->limit(5)
                ->get(['id', 'invoice', 'customer_id', 'due_date', 'total', 'paid', 'status'])
                ->map(function ($item) {
                    $remaining = max(0, ($item->total ?? 0) - ($item->paid ?? 0));
                    return [
                        'id'       => $item->id,
                        'title'    => "Piutang: {$item->invoice}",
                        'subtitle' => 'Sisa ' . number_format($remaining, 0, ',', '.'),
                        'time'     => optional($item->due_date)->diffForHumans(),
                        'status'   => $item->status,
                    ];
                });

            $payableNotifications = Payable::whereNot('status', 'paid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', now()->addDays(3))
                ->orderBy('due_date')
                ->limit(5)
                ->get(['id', 'document_number', 'due_date', 'total', 'paid', 'status'])
                ->map(function ($item) {
                    $remaining = max(0, ($item->total ?? 0) - ($item->paid ?? 0));
                    return [
                        'id'       => $item->id,
                        'title'    => "Hutang: {$item->document_number}",
                        'subtitle' => 'Sisa ' . number_format($remaining, 0, ',', '.'),
                        'time'     => optional($item->due_date)->diffForHumans(),
                        'status'   => $item->status,
                    ];
                });

            $activeShift = CashierShift::query()
                ->with('user:id,name')
                ->open()
                ->where('user_id', $userId)
                ->latest('opened_at')
                ->first();

            if ($activeShift) {
                $activeCashierShift = app(CashierShiftService::class)->summarizeForDisplay($activeShift);
            }
        }

        $storeProfile = [
            'name'    => 'Toko Anda',
            'logo'    => null,
            'address' => '',
            'phone'   => '',
            'email'   => '',
            'website' => '',
            'city'    => '',
        ];

        if (Schema::hasTable('settings')) {
            $logo = \App\Models\Setting::get('store_logo');
            if ($logo && !str_starts_with($logo, 'http') && !str_starts_with($logo, '/storage')) {
                $logo = asset('storage/' . ltrim($logo, '/'));
            }

            $storeProfile = [
                'name'    => \App\Models\Setting::get('store_name', 'Toko Anda'),
                'logo'    => $logo,
                'address' => \App\Models\Setting::get('store_address', ''),
                'phone'   => \App\Models\Setting::get('store_phone', ''),
                'email'   => \App\Models\Setting::get('store_email', ''),
                'website' => \App\Models\Setting::get('store_website', ''),
                'city'    => \App\Models\Setting::get('store_city', ''),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => !$isCustomerPortal ? $request->user() : null,
                'permissions' => (!$isCustomerPortal && $request->user() instanceof \App\Models\User) ? $request->user()->getPermissions() : [],
                'super' => (!$isCustomerPortal && $request->user() instanceof \App\Models\User) ? $request->user()->isSuperAdmin() : false,
            ],
            'customerAuth'            => $customerAuth,
            'lowStockNotifications'   => $lowStockNotifications,
            'receivableNotifications' => $receivableNotifications,
            'payableNotifications'    => $payableNotifications,
            'activeCashierShift'      => $activeCashierShift,
            'storeProfile'            => $storeProfile,
        ];
    }
}
