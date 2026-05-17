<?php

use App\Http\Controllers\Apps\AuditLogController;
use App\Http\Controllers\Apps\BankAccountController;
use App\Http\Controllers\Apps\CashierShiftController;
use App\Http\Controllers\Apps\CategoryController;
use App\Http\Controllers\Apps\CustomerController;
use App\Http\Controllers\Apps\MembershipController;
use App\Http\Controllers\Apps\MembershipPlanController;
use App\Http\Controllers\Apps\PayableController;
use App\Http\Controllers\Apps\PaymentSettingController;
use App\Http\Controllers\Apps\ProductController;
use App\Http\Controllers\Apps\PurchaseController;
use App\Http\Controllers\Apps\PurchaseReturnController;
use App\Http\Controllers\Apps\ReceivableController;
use App\Http\Controllers\Apps\SalesReturnController;
use App\Http\Controllers\Apps\SettingController;
use App\Http\Controllers\Apps\StockMutationController;
use App\Http\Controllers\Apps\StockOpnameController;
use App\Http\Controllers\Apps\SupplierController;
use App\Http\Controllers\Apps\TransactionController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\Reports\ProfitReportController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard/access', function () {
    return Inertia::render('Dashboard/Access');
})->middleware(['auth'])->name('dashboard.access');

// Public share routes (no login)
Route::get('/share/transactions/{invoice}', [DocumentController::class, 'publicInvoice'])
    ->name('transactions.public');

Route::group(['prefix' => 'dashboard', 'middleware' => ['auth']], function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'permission:dashboard-access'])->name('dashboard');
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions-access')->name('permissions.index');
    // roles route
    Route::resource('/roles', RoleController::class)
        ->except(['create', 'edit', 'show'])
        ->middlewareFor('index', 'permission:roles-access')
        ->middlewareFor('store', 'permission:roles-create')
        ->middlewareFor('update', 'permission:roles-update')
        ->middlewareFor('destroy', 'permission:roles-delete');
    // users route
    Route::resource('/users', UserController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:users-access')
        ->middlewareFor(['create', 'store'], 'permission:users-create')
        ->middlewareFor(['edit', 'update'], 'permission:users-update')
        ->middlewareFor('destroy', 'permission:users-delete');
    Route::post('/notifications/low-stock/read', [NotificationController::class, 'markLowStockRead'])->name('notifications.stock.read');
    Route::post('/notifications/low-stock/read-all', [NotificationController::class, 'markAllLowStockRead'])->name('notifications.stock.readAll');
    Route::get('/regions/regencies', [RegionController::class, 'regencies'])->name('regions.regencies');
    Route::get('/regions/districts', [RegionController::class, 'districts'])->name('regions.districts');
    Route::get('/regions/villages', [RegionController::class, 'villages'])->name('regions.villages');

    Route::resource('categories', CategoryController::class)
        ->middlewareFor(['index', 'show'], 'permission:categories-access')
        ->middlewareFor(['create', 'store'], 'permission:categories-create')
        ->middlewareFor(['edit', 'update'], 'permission:categories-edit')
        ->middlewareFor('destroy', 'permission:categories-delete');
    Route::resource('products', ProductController::class)
        ->middlewareFor(['index', 'show'], 'permission:products-access')
        ->middlewareFor(['create', 'store'], 'permission:products-create')
        ->middlewareFor(['edit', 'update'], 'permission:products-edit')
        ->middlewareFor('destroy', 'permission:products-delete');
    Route::get('stock-opnames', [StockOpnameController::class, 'index'])->middleware('permission:stock-opnames-access')->name('stock-opnames.index');
    Route::get('stock-opnames/create', [StockOpnameController::class, 'create'])->middleware('permission:stock-opnames-create')->name('stock-opnames.create');
    Route::post('stock-opnames', [StockOpnameController::class, 'store'])->middleware('permission:stock-opnames-create')->name('stock-opnames.store');
    Route::get('stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->middleware('permission:stock-opnames-access')->name('stock-opnames.show');
    Route::patch('stock-opnames/{stockOpname}', [StockOpnameController::class, 'update'])->middleware('permission:stock-opnames-create')->name('stock-opnames.update');
    Route::post('stock-opnames/{stockOpname}/items', [StockOpnameController::class, 'storeItem'])->middleware('permission:stock-opnames-create')->name('stock-opnames.items.store');
    Route::patch('stock-opnames/{stockOpname}/items/{item}', [StockOpnameController::class, 'updateItem'])->middleware('permission:stock-opnames-create')->name('stock-opnames.items.update');
    Route::post('stock-opnames/{stockOpname}/finalize', [StockOpnameController::class, 'finalize'])->middleware('permission:stock-opnames-finalize')->name('stock-opnames.finalize');
    Route::get('stock-mutations', [StockMutationController::class, 'index'])->middleware('permission:stock-mutations-access')->name('stock-mutations.index');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit-logs-access')->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('permission:audit-logs-access')->name('audit-logs.show');
    Route::get('cashier-shifts', [CashierShiftController::class, 'index'])->middleware('permission:cashier-shifts-access')->name('cashier-shifts.index');
    Route::post('cashier-shifts', [CashierShiftController::class, 'store'])->middleware('permission:cashier-shifts-open')->name('cashier-shifts.store');
    Route::get('cashier-shifts/{cashierShift}', [CashierShiftController::class, 'show'])->middleware('permission:cashier-shifts-access')->name('cashier-shifts.show');
    Route::post('cashier-shifts/{cashierShift}/close', [CashierShiftController::class, 'close'])->middleware('permission:cashier-shifts-close')->name('cashier-shifts.close');
    Route::resource('customers', CustomerController::class)
        ->middlewareFor(['index', 'show'], 'permission:customers-access')
        ->middlewareFor(['create', 'store'], 'permission:customers-create')
        ->middlewareFor(['edit', 'update'], 'permission:customers-edit')
        ->middlewareFor('destroy', 'permission:customers-delete');

    // membership plans
    Route::resource('membership-plans', MembershipPlanController::class)->except('show');

    // membership management
    Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::get('memberships/check-in', [MembershipController::class, 'checkInPage'])->name('memberships.check-in');
    Route::post('memberships/check-in', [MembershipController::class, 'checkIn'])->name('memberships.check-in.store');
    Route::get('memberships/stats', [MembershipController::class, 'stats'])->name('memberships.stats');
    Route::get('memberships/daily-log', [MembershipController::class, 'dailyLog'])->name('memberships.daily-log');

    // route customer history
    Route::get('/customers/{customer}/history', [CustomerController::class, 'getHistory'])->middleware('permission:transactions-access')->name('customers.history');

    // route customer store via AJAX (no redirect)
    Route::post('/customers/store-ajax', [CustomerController::class, 'storeAjax'])->middleware('permission:customers-create')->name('customers.storeAjax');

    // route transaction
    Route::get('/transactions', [TransactionController::class, 'index'])->middleware('permission:transactions-access')->name('transactions.index');

    // route transaction searchProduct
    Route::post('/transactions/searchProduct', [TransactionController::class, 'searchProduct'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.searchProduct');

    // route transaction addToCart
    Route::post('/transactions/addToCart', [TransactionController::class, 'addToCart'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.addToCart');

    // route transaction destroyCart
    Route::delete('/transactions/{cart_id}/destroyCart', [TransactionController::class, 'destroyCart'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.destroyCart');

    // route transaction updateCart
    Route::patch('/transactions/{cart_id}/updateCart', [TransactionController::class, 'updateCart'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.updateCart');

    // route hold transaction
    Route::post('/transactions/hold', [TransactionController::class, 'holdCart'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.hold');
    Route::post('/transactions/{holdId}/resume', [TransactionController::class, 'resumeCart'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.resume');
    Route::delete('/transactions/{holdId}/clearHold', [TransactionController::class, 'clearHold'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.clearHold');
    Route::get('/transactions/held', [TransactionController::class, 'getHeldCarts'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.held');

    // route transaction store
    Route::post('/transactions/store', [TransactionController::class, 'store'])->middleware(['permission:transactions-access', 'active_shift'])->name('transactions.store');
    Route::get('/transactions/{invoice}/print', [TransactionController::class, 'print'])->middleware('permission:transactions-access')->name('transactions.print');
    Route::get('/transactions/history', [TransactionController::class, 'history'])->middleware('permission:transactions-access')->name('transactions.history');
    Route::get('/transactions/history/{transaction}/sales-return/create', [SalesReturnController::class, 'create'])->middleware('permission:sales-returns-create')->name('sales-returns.create');
    Route::post('/transactions/history/{transaction}/sales-return', [SalesReturnController::class, 'store'])->middleware('permission:sales-returns-create')->name('sales-returns.store');
    Route::get('/sales-returns', [SalesReturnController::class, 'index'])->middleware('permission:sales-returns-access')->name('sales-returns.index');
    Route::get('/sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])->middleware('permission:sales-returns-access')->name('sales-returns.show');
    Route::patch('/sales-returns/{salesReturn}', [SalesReturnController::class, 'update'])->middleware('permission:sales-returns-create')->name('sales-returns.update');
    Route::post('/sales-returns/{salesReturn}/complete', [SalesReturnController::class, 'complete'])->middleware('permission:sales-returns-complete')->name('sales-returns.complete');
    // receivables (nota barang)
    Route::get('/receivables', [ReceivableController::class, 'index'])->middleware('permission:receivables-access')->name('receivables.index');
    Route::get('/receivables/{receivable}', [ReceivableController::class, 'show'])->middleware('permission:receivables-access')->name('receivables.show');
    Route::post('/receivables/{receivable}/pay', [ReceivableController::class, 'pay'])->middleware('permission:receivables-pay')->name('receivables.pay');
    // suppliers & payables
    Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('permission:suppliers-access')->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers-access')->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers-access')->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers-access')->name('suppliers.destroy');

    // purchases
    Route::get('purchases', [PurchaseController::class, 'index'])->middleware('permission:purchases-access')->name('purchases.index');
    Route::get('purchases/create', [PurchaseController::class, 'create'])->middleware('permission:purchases-create')->name('purchases.create');
    Route::post('purchases', [PurchaseController::class, 'store'])->middleware('permission:purchases-create')->name('purchases.store');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->middleware('permission:purchases-access')->name('purchases.show');
    Route::patch('purchases/{purchase}', [PurchaseController::class, 'update'])->middleware('permission:purchases-create')->name('purchases.update');
    Route::post('purchases/{purchase}/items', [PurchaseController::class, 'storeItem'])->middleware('permission:purchases-create')->name('purchases.items.store');
    Route::patch('purchases/{purchase}/items/{item}', [PurchaseController::class, 'updateItem'])->middleware('permission:purchases-create')->name('purchases.items.update');
    Route::delete('purchases/{purchase}/items/{item}', [PurchaseController::class, 'destroyItem'])->middleware('permission:purchases-create')->name('purchases.items.destroy');
    Route::post('purchases/{purchase}/finalize', [PurchaseController::class, 'finalize'])->middleware('permission:purchases-finalize')->name('purchases.finalize');

    // purchase returns
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->middleware('permission:purchase-returns-access')->name('purchase-returns.index');
    Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->middleware('permission:purchase-returns-create')->name('purchase-returns.create');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->middleware('permission:purchase-returns-create')->name('purchase-returns.store');
    Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->middleware('permission:purchase-returns-access')->name('purchase-returns.show');
    Route::patch('purchase-returns/{purchaseReturn}/items/{item}', [PurchaseReturnController::class, 'updateItem'])->middleware('permission:purchase-returns-create')->name('purchase-returns.items.update');
    Route::post('purchase-returns/{purchaseReturn}/complete', [PurchaseReturnController::class, 'complete'])->middleware('permission:purchase-returns-complete')->name('purchase-returns.complete');

    Route::get('/payables', [PayableController::class, 'index'])->middleware('permission:payables-access')->name('payables.index');
    Route::post('/payables', [PayableController::class, 'store'])->middleware('permission:payables-access')->name('payables.store');
    Route::get('/payables/{payable}', [PayableController::class, 'show'])->middleware('permission:payables-access')->name('payables.show');
    Route::post('/payables/{payable}/pay', [PayableController::class, 'pay'])->middleware('permission:payables-pay')->name('payables.pay');

    // pdf documents
    Route::get('/documents/transactions/{invoice}/pdf/invoice', [DocumentController::class, 'invoice'])->middleware('permission:transactions-access')->name('pdf.transactions.invoice');
    Route::get('/documents/transactions/{invoice}/pdf/receipt/{size?}', [DocumentController::class, 'receipt'])->middleware('permission:transactions-access')->name('pdf.transactions.receipt');
    Route::get('/documents/transactions/{invoice}/pdf/shipping', [DocumentController::class, 'shipping'])->middleware('permission:transactions-access')->name('pdf.transactions.shipping');
    Route::get('/documents/receivables/{receivable}/pdf', [DocumentController::class, 'receivable'])->middleware('permission:receivables-access')->name('pdf.receivables.show');
    Route::get('/documents/payables/{payable}/pdf', [DocumentController::class, 'payable'])->middleware('permission:payables-access')->name('pdf.payables.show');

    Route::get('/settings/payments', [PaymentSettingController::class, 'edit'])->middleware('permission:payment-settings-access')->name('settings.payments.edit');
    Route::put('/settings/payments', [PaymentSettingController::class, 'update'])->middleware('permission:payment-settings-access')->name('settings.payments.update');

    // settings target penjualan
    Route::get('/settings/target', [SettingController::class, 'target'])->middleware('permission:dashboard-access')->name('settings.target');
    Route::post('/settings/target', [SettingController::class, 'updateTarget'])->middleware('permission:dashboard-access')->name('settings.target.update');
    Route::get('/settings/store', [SettingController::class, 'storeProfile'])->middleware('permission:dashboard-access')->name('settings.store');
    Route::post('/settings/store', [SettingController::class, 'updateStoreProfile'])->middleware('permission:dashboard-access')->name('settings.store.update');

    // settings bank accounts
    Route::get('/settings/bank-accounts', [BankAccountController::class, 'index'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.index');
    Route::get('/settings/bank-accounts/create', [BankAccountController::class, 'create'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.create');
    Route::post('/settings/bank-accounts', [BankAccountController::class, 'store'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.store');
    Route::get('/settings/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.edit');
    Route::put('/settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.update');
    Route::delete('/settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.destroy');
    Route::patch('/settings/bank-accounts/{bankAccount}/toggle', [BankAccountController::class, 'toggleActive'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.toggle');
    Route::post('/settings/bank-accounts/order', [BankAccountController::class, 'updateOrder'])->middleware('permission:payment-settings-access')->name('settings.bank-accounts.order');

    // confirm payment for bank transfer
    Route::patch('/transactions/{transaction}/confirm-payment', [TransactionController::class, 'confirmPayment'])->middleware('permission:transactions-access')->name('transactions.confirm-payment');

    // reports
    Route::get('/reports/sales', [SalesReportController::class, 'index'])->middleware('permission:reports-access')->name('reports.sales.index');
    Route::get('/reports/profits', [ProfitReportController::class, 'index'])->middleware('permission:profits-access')->name('reports.profits.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Customer Portal ───────────────────────────────────────────────
Route::prefix('customer')->group(function () {
    // Guest (belum login sebagai customer)
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('customer.login');
        Route::post('login', [AuthController::class, 'login']);
    });

    // Authenticated customer
    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('customer.dashboard');
        Route::get('transactions', [App\Http\Controllers\Customer\TransactionController::class, 'index'])->name('customer.transactions.index');
        Route::get('transactions/{invoice}', [App\Http\Controllers\Customer\TransactionController::class, 'show'])->name('customer.transactions.show');
        Route::get('membership', [App\Http\Controllers\Customer\MembershipController::class, 'index'])->name('customer.membership');
        Route::get('membership/plans', [App\Http\Controllers\Customer\MembershipController::class, 'plans'])->name('customer.membership.plans');
        Route::get('membership/history', [App\Http\Controllers\Customer\MembershipController::class, 'history'])->name('customer.membership.history');
        Route::post('membership/purchase', [App\Http\Controllers\Customer\MembershipController::class, 'purchase'])->name('customer.membership.purchase');
        Route::get('profile', [App\Http\Controllers\Customer\ProfileController::class, 'show'])->name('customer.profile');
        Route::patch('profile', [App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('customer.profile.update');
        Route::post('logout', [AuthController::class, 'logout'])->name('customer.logout');
    });
});

require __DIR__.'/auth.php';
