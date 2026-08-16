<?php

use App\Http\Controllers\Ecommerce\OrderController;
use App\Http\Controllers\Ecommerce\PageController;
use App\Http\Controllers\Ecommerce\ServiceController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\InvoiceController;
use App\Http\Controllers\Erp\UserController;
use App\Http\Controllers\Erp\ProductVariationController;
use App\Http\Controllers\Erp\VariationAttributeController;
use App\Http\Controllers\Erp\ProductVariationStockController;
use App\Http\Controllers\Erp\BarcodeController;
use App\Http\Controllers\Erp\ChartOfAccountController;
use App\Http\Controllers\Erp\JournalController;
use App\Http\Controllers\Erp\DoubleEntryReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

// Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/', [PageController::class, 'index'])->name('ecommerce.home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/products', [PageController::class, 'products'])->name('product.archive');
Route::match(['get', 'post'], '/products/filter', [PageController::class, 'filterProducts'])->name('products.filter');
Route::get('/product/{slug}', [PageController::class, 'productDetails'])->name('product.details');
Route::get('/combo/{slug}', [PageController::class, 'comboDetails'])->name('combo.details');

// Review API Routes
Route::get('/api/products/{productId}/reviews', [\App\Http\Controllers\Ecommerce\ReviewController::class, 'getProductReviews'])->name('api.reviews.product');
Route::post('/api/products/{productId}/reviews', [\App\Http\Controllers\Ecommerce\ReviewController::class, 'store'])->name('api.reviews.store');
Route::put('/api/products/{productId}/reviews/{reviewId}', [\App\Http\Controllers\Ecommerce\ReviewController::class, 'update'])->name('api.reviews.update');
Route::delete('/api/products/{productId}/reviews/{reviewId}', [\App\Http\Controllers\Ecommerce\ReviewController::class, 'destroy'])->name('api.reviews.destroy');

// Test route for review system
Route::get('/test-review', function() {
    return response()->json([
        'message' => 'Review system is working',
        'csrf_token' => csrf_token(),
        'user_authenticated' => Auth::check(),
        'user_id' => Auth::id()
    ]);
});

Route::get('/reset-financial-account-balances', function() {
    if (!auth()->check()) {
        return response('Unauthorized. Please login first.', 403);
    }
    \App\Models\FinancialAccount::query()->update(['balance' => 0]);
    if (class_exists('\App\Http\Controllers\Erp\DashboardController')) {
        \App\Http\Controllers\Erp\DashboardController::clearCache();
    }
    return response('All Financial Account Balances have been reset to 0 successfully!');
});

Route::get('/categories', [PageController::class, 'categories'])->name('categories');
Route::get('/best-deal', [PageController::class, 'bestDeals'])->name('best.deal');
// Removed service archive and details routes
Route::get('/vlogs', [PageController::class, 'vlogs'])->name('vlogs');
Route::get('/pages/{slug}', [PageController::class, 'additionalPage'])->name('additionalPage.show');
Route::get('/additional-pages/{slug}', [PageController::class, 'additionalPage'])->name('ecommerce.additionalPages.show');


Route::get('/invoice/print/{invoice_number}', [InvoiceController::class, 'print'])->name('invoice.print');
Route::get('/search', [PageController::class, 'search'])->name('search');

Route::get('/dashboard', function () {
    return redirect()->route('erp.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Test routes for role and permission system
Route::middleware('auth')->group(function () {
    Route::get('/test/role/admin', function () {
        return 'You have Admin role!';
    })->middleware('role:Admin')->name('test.role.admin');

    Route::get('/test/permission/view-products', function () {
        return 'You have view products permission!';
    })->middleware('permission:view products')->name('test.permission.view-products');
});


Route::middleware('auth')->group(function () {
    // Service functionality disabled - commented out
    // Route::get('/request-service', [ServiceController::class, 'request'])->name('service.request');
    // Route::post('/request-service', [ServiceController::class, 'submitRequest'])->name('service.request.submit');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/filter-orders', [ProfileController::class, 'filterOrders'])->name('profile.filter.orders');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Order management (auth-only)
    Route::post('/cancel-order/{orderId}', [OrderController::class, 'cancelOrder'])->name('order.cancel');
    Route::delete('/delete-order/{orderId}', [OrderController::class, 'deleteOrder'])->name('order.delete');

    // Wishlist
    Route::get('/wishlists', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'index'])->name('wishlist.index');
    Route::delete('/remove-wishlis', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'removeAllWishlist'])->name('wishlist.removeAll');

    // Service functionality disabled - commented out
    // Route::get('/requested-service/{service_number}', [ServiceController::class, 'show'])->name('service.request.show');
});
// Guest-accessible Checkout and Order routes
Route::get('/checkout', [OrderController::class, 'checkoutPage'])->name('checkout');
Route::post('/make-order', [OrderController::class, 'makeOrder'])->name('order.make');
Route::get('/order-success/{orderId}', [OrderController::class, 'orderSuccess'])->name('order.success');
Route::get('/order-details/{orderNum}', [OrderController::class, 'show'])->name('order.details');
Route::get('/order/{orderNumber}/invoice/download', [OrderController::class, 'downloadInvoice'])->name('order.invoice.download');

// City and Shipping API routes
Route::get('/api/cities/search', [OrderController::class, 'searchCities'])->name('api.cities.search');
Route::get('/api/shipping-methods/city', [OrderController::class, 'getShippingMethodsForCity'])->name('api.shipping.methods.city');
Route::post('/api/coupons/validate', [OrderController::class, 'validateCoupon'])->name('api.coupons.validate');

// Payment initialization and status should be accessible to guests
Route::post('/payment/initialize', [\App\Http\Controllers\PaymentController::class, 'initializePayment'])->name('payment.initialize');
Route::get('/payment/status/{tranId}', [\App\Http\Controllers\PaymentController::class, 'getPaymentStatus'])->name('payment.status');

// Payment Routes (outside auth middleware for SSL Commerce callbacks)
// These routes need to be excluded from CSRF protection for SSL Commerce callbacks
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/payment/ssl-form/{form_id}', [\App\Http\Controllers\PaymentController::class, 'showSslForm'])->name('payment.ssl-form');
    Route::match(['get', 'post'], '/payment/success', [\App\Http\Controllers\PaymentController::class, 'paymentSuccess'])->name('payment.success');
    Route::match(['get', 'post'], '/payment/failed', [\App\Http\Controllers\PaymentController::class, 'paymentFailed'])->name('payment.failed');
    Route::match(['get', 'post'], '/payment/cancelled', [\App\Http\Controllers\PaymentController::class, 'paymentCancelled'])->name('payment.cancelled');
    Route::post('/payment/ipn', [\App\Http\Controllers\PaymentController::class, 'handleIpn'])->name('payment.ipn');
});

Route::prefix('erp')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('erp.dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('erp.dashboard.data');
    Route::get('/profile', [\App\Http\Controllers\Erp\ProfileController::class, 'show'])->name('erp.profile');
    Route::put('/profile', [\App\Http\Controllers\Erp\ProfileController::class, 'update'])->name('erp.profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Erp\ProfileController::class, 'updatePassword'])->name('erp.profile.password');

    Route::get('/all-users', [UserController::class, 'fetchUser'])->name('user.fetch');
    Route::get('/users/search', [UserController::class, 'searchUser'])->name('user.search');

    Route::get('/branches/fetch', [\App\Http\Controllers\Erp\BranchController::class, 'fetchBranches']);
    Route::get('/branches/{id}/check-delete', [\App\Http\Controllers\Erp\BranchController::class, 'checkDelete'])->name('branches.check-delete');

    // Branch Report Routes
    Route::get('/branches/report-data', [\App\Http\Controllers\Erp\BranchController::class, 'getReportData'])->name('branches.report.data');
    Route::get('/branches/export-excel', [\App\Http\Controllers\Erp\BranchController::class, 'exportExcel'])->name('branches.export.excel');
    Route::get('/branches/export-pdf', [\App\Http\Controllers\Erp\BranchController::class, 'exportPdf'])->name('branches.export.pdf');

    Route::resource('branches', \App\Http\Controllers\Erp\BranchController::class);
    Route::resource('warehouses', \App\Http\Controllers\Erp\WarehouseController::class);
    Route::resource('materials', \App\Http\Controllers\Erp\MaterialController::class);
    Route::resource('banners', \App\Http\Controllers\Erp\BannerController::class);
    
    // Coupon Management
    Route::resource('coupons', \App\Http\Controllers\Erp\CouponController::class);
    Route::patch('/coupons/{coupon}/toggle-status', [\App\Http\Controllers\Erp\CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');
    
    // Bulk Discount Management
    Route::resource('bulk-discounts', \App\Http\Controllers\Erp\BulkDiscountController::class);
    Route::patch('/bulk-discounts/{bulkDiscount}/toggle-status', [\App\Http\Controllers\Erp\BulkDiscountController::class, 'toggleStatus'])->name('bulk-discounts.toggle-status');
    
    // Review Management
    Route::get('/reviews', [\App\Http\Controllers\Erp\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{id}', [\App\Http\Controllers\Erp\ReviewController::class, 'show'])->name('reviews.show');
    Route::post('/reviews/{id}/approve', [\App\Http\Controllers\Erp\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{id}/reject', [\App\Http\Controllers\Erp\ReviewController::class, 'reject'])->name('reviews.reject');
    Route::post('/reviews/{id}/toggle-featured', [\App\Http\Controllers\Erp\ReviewController::class, 'toggleFeatured'])->name('reviews.toggle-featured');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Erp\ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/bulk-action', [\App\Http\Controllers\Erp\ReviewController::class, 'bulkAction'])->name('reviews.bulk-action');
    Route::get('/reviews/statistics', [\App\Http\Controllers\Erp\ReviewController::class, 'statistics'])->name('reviews.statistics');
    
    Route::patch('/banners/{banner}/toggle-status', [\App\Http\Controllers\Erp\BannerController::class, 'toggleStatus'])->name('banners.toggle-status');
    Route::resource('warehouse-product-stocks', \App\Http\Controllers\Erp\WarehouseProductStockController::class);
    Route::resource('branch-product-stocks', \App\Http\Controllers\Erp\BranchProductStockController::class);
    Route::resource('employee-product-stocks', \App\Http\Controllers\Erp\EmployeeProductStockController::class);
    
    // Salary Payment Routes
    Route::get('/salary', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'index'])->name('salary.index');
    Route::get('/salary/create', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'create'])->name('salary.create');
    Route::post('/salary/store', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'store'])->name('salary.store');
    Route::get('/salary/details', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'getSalaryDetails'])->name('salary.details');
    Route::post('/salary/calculate-bonus', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'calculateBonus'])->name('salary.calculate.bonus');
    Route::put('/salary/{id}/update-bonus', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'updateBonus'])->name('salary.update.bonus');
    Route::get('/salary/{id}', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'show'])->name('salary.show');
    Route::delete('/salary/{id}', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'destroy'])->name('salary.destroy');
    Route::get('/salary-export/excel', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'exportExcel'])->name('salary.export.excel');
    Route::get('/salary-export/pdf', [\App\Http\Controllers\Erp\SalaryPaymentController::class, 'exportPdf'])->name('salary.export.pdf');

    // Sales Target Routes
    Route::get('/sales-targets', [\App\Http\Controllers\Erp\SalesTargetController::class, 'index'])->name('sales-targets.index');
    Route::get('/sales-targets/create', [\App\Http\Controllers\Erp\SalesTargetController::class, 'create'])->name('sales-targets.create');
    Route::post('/sales-targets', [\App\Http\Controllers\Erp\SalesTargetController::class, 'store'])->name('sales-targets.store');
    Route::get('/sales-targets/{id}', [\App\Http\Controllers\Erp\SalesTargetController::class, 'show'])->name('sales-targets.show');
    Route::get('/sales-targets/{id}/edit', [\App\Http\Controllers\Erp\SalesTargetController::class, 'edit'])->name('sales-targets.edit');
    Route::put('/sales-targets/{id}', [\App\Http\Controllers\Erp\SalesTargetController::class, 'update'])->name('sales-targets.update');
    Route::post('/sales-targets/{id}/update-achievement', [\App\Http\Controllers\Erp\SalesTargetController::class, 'updateAchievement'])->name('sales-targets.update.achievement');
    Route::delete('/sales-targets/{id}', [\App\Http\Controllers\Erp\SalesTargetController::class, 'destroy'])->name('sales-targets.destroy');
    Route::get('/sales-targets/export/excel', [\App\Http\Controllers\Erp\SalesTargetController::class, 'exportExcel'])->name('sales-targets.export.excel');
    Route::get('/sales-targets/export/pdf', [\App\Http\Controllers\Erp\SalesTargetController::class, 'exportPdf'])->name('sales-targets.export.pdf');

    // Salary Increment Routes
    Route::get('/salary-increments', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'index'])->name('salary-increments.index');
    Route::get('/salary-increments/create', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'create'])->name('salary-increments.create');
    Route::post('/salary-increments', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'store'])->name('salary-increments.store');
    Route::get('/salary-increments/{id}', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'show'])->name('salary-increments.show');
    Route::get('/salary-increments/{id}/edit', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'edit'])->name('salary-increments.edit');
    Route::put('/salary-increments/{id}', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'update'])->name('salary-increments.update');
    Route::get('/salary-increments/get-employee-salary-info/{employeeId}', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'getEmployeeSalaryInfo'])->name('salary-increments.get-employee-salary-info');
    Route::get('/salary-increments/export/excel', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'exportExcel'])->name('salary-increments.export.excel');
    Route::get('/salary-increments/export/pdf', [\App\Http\Controllers\Erp\SalaryIncrementController::class, 'exportPdf'])->name('salary-increments.export.pdf');

    Route::get('/employees/fetch', [\App\Http\Controllers\Erp\EmployeeController::class, 'fetchEmployees']);
    Route::get('/branches/{branch}/non-branch-employees', [\App\Http\Controllers\Erp\BranchController::class, 'getNonBranchEmployee'])->name('branches.non_branch_employees');
    Route::post('/branches/{branch}/add-employee/{employee}', [\App\Http\Controllers\Erp\BranchController::class, 'addEmployee'])->name('branches.add_employee');
    Route::post('/branches/remove-employee/{employee}', [\App\Http\Controllers\Erp\BranchController::class, 'removeEmployeeFromBranch'])->name('branches.remove_employee');
    Route::delete('/branches/products/{id}', [\App\Http\Controllers\Erp\BranchController::class, 'removeProduct'])->name('branches.products.remove');
    Route::post('/branches/{branch}/warehouses', [\App\Http\Controllers\Erp\WarehouseController::class, 'storeWarehousePerBranch'])->name('branches.warehouses.store');

    // Master Settings Dashboard
    Route::get('/master-settings', [\App\Http\Controllers\Erp\MasterSettingController::class, 'index'])->name('master.settings');

    // Attribute Management
    Route::resource('brands', \App\Http\Controllers\Erp\BrandController::class);
    Route::resource('seasons', \App\Http\Controllers\Erp\SeasonController::class);
    Route::resource('genders', \App\Http\Controllers\Erp\GenderController::class);
    Route::resource('units', \App\Http\Controllers\Erp\UnitController::class);
    Route::resource('suppliers', \App\Http\Controllers\Erp\SupplierController::class);
    Route::get('suppliers/{supplier}/ledger', [\App\Http\Controllers\Erp\SupplierController::class, 'ledger'])->name('suppliers.ledger');
    Route::get('supplier-payments/export-excel', [\App\Http\Controllers\Erp\SupplierPaymentController::class, 'exportExcel'])->name('supplier-payments.export.excel');
    Route::get('supplier-payments/export-pdf', [\App\Http\Controllers\Erp\SupplierPaymentController::class, 'exportPdf'])->name('supplier-payments.export.pdf');
    Route::get('supplier-payments/get-bills/{supplierId}', [\App\Http\Controllers\Erp\SupplierPaymentController::class, 'getSupplierBills'])->name('supplier-payments.get-bills');
    Route::get('supplier-payments/pay-on-sale', [\App\Http\Controllers\Erp\SupplierPaymentController::class, 'payOnSale'])->name('supplier-payments.pay-on-sale');
    Route::get('supplier-payments/pay-on-sale-summary', [\App\Http\Controllers\Erp\SupplierPaymentController::class, 'getPayOnSaleSummary'])->name('supplier-payments.pay-on-sale-summary');
    Route::post('supplier-payments/pay-on-sale', [\App\Http\Controllers\Erp\SupplierPaymentController::class, 'storePayOnSale'])->name('supplier-payments.store-pay-on-sale');
    Route::resource('supplier-payments', \App\Http\Controllers\Erp\SupplierPaymentController::class);

    // Fund Transfers (Branch <-> Warehouse Cash/Bank Transfer)
    Route::get('fund-transfers/export-excel', [\App\Http\Controllers\Erp\TransferController::class, 'exportExcel'])->name('transfers.export.excel');
    Route::get('fund-transfers/export-pdf', [\App\Http\Controllers\Erp\TransferController::class, 'exportPdf'])->name('transfers.export.pdf');
    Route::get('fund-transfers', [\App\Http\Controllers\Erp\TransferController::class, 'index'])->name('transfers.index');
    Route::get('fund-transfers/create', [\App\Http\Controllers\Erp\TransferController::class, 'create'])->name('transfers.create');
    Route::post('fund-transfers', [\App\Http\Controllers\Erp\TransferController::class, 'store'])->name('transfers.store');
    Route::get('fund-transfers/{transfer}', [\App\Http\Controllers\Erp\TransferController::class, 'show'])->name('transfers.show');
    Route::delete('fund-transfers/{transfer}', [\App\Http\Controllers\Erp\TransferController::class, 'destroy'])->name('transfers.destroy');
    Route::get('fund-transfers/get-accounts-by-location', [\App\Http\Controllers\Erp\TransferController::class, 'getAccountsByLocation'])->name('transfers.get-accounts');

    // Advanced Reporting
    Route::get('/money-receipt/export-excel', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'exportExcel'])->name('money-receipt.export.excel');
    Route::get('/money-receipt/export-pdf', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'exportPdf'])->name('money-receipt.export.pdf');
    Route::get('/invoices/search', [\App\Http\Controllers\Erp\InvoiceController::class, 'invoiceSearch'])->name('invoices.search');
    Route::resource('money-receipt', \App\Http\Controllers\Erp\MoneyReceiptController::class);
    Route::get('/money-receipt/get-due-invoices/{customerId}', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'getDueInvoices'])->name('money-receipt.get-due-invoices');
    Route::get('/money-receipt/get-invoice/{invoiceId}', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'getInvoiceDetails'])->name('money-receipt.get-invoice');

    Route::get('/reports', [\App\Http\Controllers\Erp\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/purchases', [\App\Http\Controllers\Erp\ReportController::class, 'purchaseReport'])->name('reports.purchase');
    Route::get('/reports/sales', [\App\Http\Controllers\Erp\ReportController::class, 'saleReport'])->name('reports.sale');
    Route::get('/reports/profit-loss', [\App\Http\Controllers\Erp\ReportController::class, 'profitLossReport'])->name('reports.profit-loss');
    Route::get('/reports/cash-profit', [\App\Http\Controllers\Erp\ReportController::class, 'cashProfitReport'])->name('reports.cash-profit');
    Route::get('/reports/cash-book', [\App\Http\Controllers\Erp\ReportController::class, 'cashBookReport'])->name('reports.cash-book');
    Route::get('/reports/bank-book', [\App\Http\Controllers\Erp\ReportController::class, 'bankBookReport'])->name('reports.bank-book');
    Route::get('/reports/mobile-book', [\App\Http\Controllers\Erp\ReportController::class, 'mobileBookReport'])->name('reports.mobile-book');
    Route::get('/reports/customer', [\App\Http\Controllers\Erp\ReportController::class, 'customerReport'])->name('reports.customer');
    Route::get('/reports/customer-ledger/{id?}', [\App\Http\Controllers\Erp\ReportController::class, 'customerLedger'])->name('reports.customer.ledger');
    Route::get('/reports/supplier', [\App\Http\Controllers\Erp\ReportController::class, 'supplierReport'])->name('reports.supplier-summary');
    Route::get('/reports/supplier-ledger/{id?}', [\App\Http\Controllers\Erp\ReportController::class, 'supplierLedger'])->name('reports.supplier.ledger');
    Route::get('/reports/stock', [\App\Http\Controllers\Erp\ReportController::class, 'stockReport'])->name('reports.stock');
    Route::get('/reports/executive', [\App\Http\Controllers\Erp\ReportController::class, 'executiveReport'])->name('reports.executive');
    Route::get('/reports/performance', [\App\Http\Controllers\Erp\ReportController::class, 'performanceReport'])->name('reports.performance');
    Route::get('/reports/expenses', [\App\Http\Controllers\Erp\ReportController::class, 'expenseReport'])->name('reports.expenses');
    Route::get('/reports/snapshot', [\App\Http\Controllers\Erp\ReportController::class, 'businessSnapshot'])->name('reports.snapshot');


    // Categories
    Route::get('/categories', [\App\Http\Controllers\Erp\ProductController::class, 'categoryList'])->name('category.list');
    Route::post('/categories', [\App\Http\Controllers\Erp\ProductController::class, 'storeCategory'])->name('category.store');
    Route::patch('/categories/{category}', [\App\Http\Controllers\Erp\ProductController::class, 'updateCategory'])->name('category.update');
    Route::delete('/categories/{category}', [\App\Http\Controllers\Erp\ProductController::class, 'deleteCategory'])->name('category.delete');
    Route::get('/categories/search', [\App\Http\Controllers\Erp\ProductController::class, 'searchCategory'])->name('category.search');

    // Subcategories
    Route::get('/subcategories', [\App\Http\Controllers\Erp\ProductController::class, 'subcategoryList'])->name('subcategory.list');
    Route::post('/subcategories', [\App\Http\Controllers\Erp\ProductController::class, 'storeSubcategory'])->name('subcategory.store');
    Route::patch('/subcategories/{subcategory}', [\App\Http\Controllers\Erp\ProductController::class, 'updateSubcategory'])->name('subcategory.update');
    Route::delete('/subcategories/{subcategory}', [\App\Http\Controllers\Erp\ProductController::class, 'deleteSubcategory'])->name('subcategory.delete');


    // Products
    Route::get('/products/search', [\App\Http\Controllers\Erp\ProductController::class, 'productSearch'])->name('products.search');
    Route::get('/products/search-by-style', [\App\Http\Controllers\Erp\ProductController::class, 'searchByStyle'])->name('products.search.by.style');
    Route::get('/products/{id}/variations-with-stock', [\App\Http\Controllers\Erp\ProductController::class, 'getVariationsWithStock'])->name('products.variations.with.stock');
    Route::get('/products/search-with-filters/{branchId}', [\App\Http\Controllers\Erp\ProductController::class, 'searchProductWithFilters'])->name('product.searchWithFilters');
    Route::get('/products/search-style', [\App\Http\Controllers\Erp\ProductController::class, 'searchStyleNumber'])->name('products.search.style');
    Route::get('/products/find-by-style/{styleNumber}', [\App\Http\Controllers\Erp\ProductController::class, 'findProductByStyle'])->name('products.find.by.style');
    Route::post('/products/find-by-barcode/{branchId}', [\App\Http\Controllers\Erp\ProductController::class, 'findProductByBarcode'])->name('products.find.by.barcode');
    Route::delete('/products/gallery/{id}', [\App\Http\Controllers\Erp\ProductController::class, 'deleteGalleryImage'])->name('product.gallery.delete');
    Route::post('/products/gallery', [\App\Http\Controllers\Erp\ProductController::class, 'addGalleryImage'])->name('product.gallery.add');
    Route::get('/products', [\App\Http\Controllers\Erp\ProductController::class, 'index'])->name('product.list');
    Route::get('/products/export/excel', [\App\Http\Controllers\Erp\ProductController::class, 'exportExcel'])->name('product.export.excel');
    Route::get('/products/export/csv', [\App\Http\Controllers\Erp\ProductController::class, 'exportCsv'])->name('product.export.csv');
    Route::get('/products/export/pdf', [\App\Http\Controllers\Erp\ProductController::class, 'exportPdf'])->name('product.export.pdf');
    Route::get('/products/new', [\App\Http\Controllers\Erp\ProductController::class, 'create'])->name('product.create');
    Route::post('/products', [\App\Http\Controllers\Erp\ProductController::class, 'store'])->name('product.store');
    Route::get('/products/{product}', [\App\Http\Controllers\Erp\ProductController::class, 'show'])->name('product.show');
    Route::get('/products/{product}/edit', [\App\Http\Controllers\Erp\ProductController::class, 'edit'])->name('product.edit');
    Route::patch('/products/{product}', [\App\Http\Controllers\Erp\ProductController::class, 'update'])->name('product.update');
    Route::delete('/products/{product}', [\App\Http\Controllers\Erp\ProductController::class, 'destroy'])->name('product.delete');
    Route::get('/products/{id}/price', [\App\Http\Controllers\Erp\ProductController::class, 'getPrice']);
    Route::get('/products/{id}/sale-price', [\App\Http\Controllers\Erp\ProductController::class, 'getSalePrice']);
    Route::get('/products/{productId}/variations-list', [\App\Http\Controllers\Erp\ProductController::class, 'getProductVariations'])->name('products.variations.list');

    // Barcode Management
    Route::get('/barcodes', [\App\Http\Controllers\Erp\BarcodeController::class, 'index'])->name('barcodes.index');
    Route::get('/barcodes/search', [\App\Http\Controllers\Erp\BarcodeController::class, 'searchByStyle'])->name('barcodes.search');
    Route::get('/barcodes/product/{productId}', [\App\Http\Controllers\Erp\BarcodeController::class, 'generateProductBarcode'])->name('barcodes.product');
    Route::get('/barcodes/variation/{productId}/{variationId}', [\App\Http\Controllers\Erp\BarcodeController::class, 'generateVariationBarcode'])->name('barcodes.variation');
    Route::post('/barcodes/bulk', [\App\Http\Controllers\Erp\BarcodeController::class, 'generateBulkBarcodes'])->name('barcodes.bulk');
    Route::get('/barcodes/print/{productId}/{variationId?}', [\App\Http\Controllers\Erp\BarcodeController::class, 'printBarcodeLabel'])->name('barcodes.print');
    Route::get('/barcodes/download/{productId}/{variationId?}', [\App\Http\Controllers\Erp\BarcodeController::class, 'downloadBarcodePDF'])->name('barcodes.download');

    // Combo Barcode Management
    Route::get('/barcodes/combo/search', [\App\Http\Controllers\Erp\BarcodeController::class, 'searchCombo'])->name('barcodes.combo.search');
    Route::get('/barcodes/combo/{comboId}', [\App\Http\Controllers\Erp\BarcodeController::class, 'generateComboBarcode'])->name('barcodes.combo.generate');
    Route::get('/barcodes/combo/{comboId}/print', [\App\Http\Controllers\Erp\BarcodeController::class, 'printComboBarcodeLabel'])->name('barcodes.combo.print');
    Route::get('/barcodes/combo/{comboId}/download', [\App\Http\Controllers\Erp\BarcodeController::class, 'downloadComboBarcodePDF'])->name('barcodes.combo.download');

    // Product Variations
    Route::prefix('products/{productId}/variations')->group(function () {
        Route::get('/', [ProductVariationController::class, 'index'])->name('erp.products.variations.index');
        Route::get('/create', [ProductVariationController::class, 'create'])->name('erp.products.variations.create');
        Route::post('/', [ProductVariationController::class, 'store'])->name('erp.products.variations.store');
        Route::get('/{variationId}', [ProductVariationController::class, 'show'])->name('erp.products.variations.show');
        Route::get('/{variationId}/edit', [ProductVariationController::class, 'edit'])->name('erp.products.variations.edit');
        Route::put('/{variationId}', [ProductVariationController::class, 'update'])->name('erp.products.variations.update');
        Route::delete('/{variationId}', [ProductVariationController::class, 'destroy'])->name('erp.products.variations.destroy');
        Route::post('/{variationId}/toggle-status', [ProductVariationController::class, 'toggleStatus'])->name('erp.products.variations.toggle-status');
        Route::get('/{variationId}/stock', [ProductVariationStockController::class, 'index'])->name('erp.products.variations.stock');
        Route::post('/{variationId}/stock/branches', [ProductVariationStockController::class, 'addStockToBranches'])->name('erp.products.variations.stock.branches');
        Route::post('/{variationId}/stock/warehouses', [ProductVariationStockController::class, 'addStockToWarehouses'])->name('erp.products.variations.stock.warehouses');
        Route::post('/{variationId}/stock/adjust', [ProductVariationStockController::class, 'adjustStock'])->name('erp.products.variations.stock.adjust');
        Route::get('/{variationId}/stock/levels', [ProductVariationStockController::class, 'getStockLevels'])->name('erp.products.variations.stock.levels');
    });

    // Variation Attributes
    Route::prefix('variation-attributes')->group(function () {
        Route::get('/', [VariationAttributeController::class, 'index'])->name('erp.variation-attributes.index');
        Route::get('/create', [VariationAttributeController::class, 'create'])->name('erp.variation-attributes.create');
        Route::post('/', [VariationAttributeController::class, 'store'])->name('erp.variation-attributes.store');
        Route::get('/{id}', [VariationAttributeController::class, 'show'])->name('erp.variation-attributes.show');
        Route::get('/{id}/edit', [VariationAttributeController::class, 'edit'])->name('erp.variation-attributes.edit');
        Route::put('/{id}', [VariationAttributeController::class, 'update'])->name('erp.variation-attributes.update');
        Route::delete('/{id}', [VariationAttributeController::class, 'destroy'])->name('erp.variation-attributes.destroy');
        Route::post('/{id}/toggle-status', [VariationAttributeController::class, 'toggleStatus'])->name('erp.variation-attributes.toggle-status');
    });

    // AJAX routes for variation attributes
    Route::get('/variation-attributes/{attributeId}/values', [ProductVariationController::class, 'getAttributeValues'])->name('erp.variation-attributes.values');

    // Combo Products Management
    Route::get('/combos', [\App\Http\Controllers\Erp\ComboProductController::class, 'list'])->name('erp.combo-products.index');
    Route::get('/combos/create', [\App\Http\Controllers\Erp\ComboProductController::class, 'create'])->name('erp.combo-products.create');
    Route::post('/combos', [\App\Http\Controllers\Erp\ComboProductController::class, 'store'])->name('erp.combo-products.store');
    Route::get('/products/{product}/combo', [\App\Http\Controllers\Erp\ComboProductController::class, 'index'])->name('erp.combo-products.manage');
    Route::post('/products/{product}/combo', [\App\Http\Controllers\Erp\ComboProductController::class, 'addItem'])->name('erp.combo-products.add-item');
    Route::delete('/combo-products/{comboProduct}', [\App\Http\Controllers\Erp\ComboProductController::class, 'destroy'])->name('erp.combo-products.destroy');
    Route::delete('/combos/{product}', [\App\Http\Controllers\Erp\ComboProductController::class, 'destroyCombo'])->name('erp.combo-products.delete');

    // Stock
    Route::get('/product-stock', [\App\Http\Controllers\Erp\StockController::class, 'stocklist'])->name('productstock.list');
    Route::get('/product-stock/export/excel', [App\Http\Controllers\Erp\StockController::class, 'exportStockExcel'])->name('productstock.export.excel');
    Route::get('/product-stock/export/pdf', [App\Http\Controllers\Erp\StockController::class, 'exportStockPdf'])->name('productstock.export.pdf');
    Route::post('/stock/add-to-branches', [\App\Http\Controllers\Erp\StockController::class, 'addStockToBranches'])->name('stock.addToBranches');
    Route::post('/stock/add-to-warehouses', [App\Http\Controllers\Erp\StockController::class, 'addStockToWarehouses'])->name('stock.addToWarehouses');
    Route::post('/stock/adjust', [\App\Http\Controllers\Erp\StockController::class, 'adjustStock'])->name('stock.adjust');
    Route::get('/stock/adjustment', [\App\Http\Controllers\Erp\StockController::class, 'adjustmentCreate'])->name('stock.adjustment.create');
    Route::get('/stock/adjustment-list', [\App\Http\Controllers\Erp\StockController::class, 'adjustmentList'])->name('stock.adjustment.list');
    Route::get('/stock/adjustment-excel', [\App\Http\Controllers\Erp\StockController::class, 'exportAdjustmentExcel'])->name('stock.adjustment.excel');
    Route::get('/stock/adjustment-pdf', [\App\Http\Controllers\Erp\StockController::class, 'exportAdjustmentPdf'])->name('stock.adjustment.pdf');
    Route::post('/stock/adjustment/store', [\App\Http\Controllers\Erp\StockController::class, 'storeAdjustment'])->name('stock.adjustment.store');
    Route::delete('/stock/adjustment/{id}', [\App\Http\Controllers\Erp\StockController::class, 'deleteAdjustment'])->name('stock.adjustment.delete');
    Route::post('/stock/update-opening', [\App\Http\Controllers\Erp\StockController::class, 'updateOpeningStock'])->name('stock.updateOpening');
    Route::get('/stock/current', [\App\Http\Controllers\Erp\StockController::class, 'getCurrentStock'])->name('stock.current');

    // Transfers
    Route::get('/stock-transfer', [\App\Http\Controllers\Erp\StockTransferController::class, 'index'])->name('stocktransfer.list');
    Route::get('/stock-transfer/create', [\App\Http\Controllers\Erp\StockTransferController::class, 'create'])->name('stocktransfer.create');
    Route::get('/stock-transfer/export-excel', [\App\Http\Controllers\Erp\StockTransferController::class, 'exportExcel'])->name('stocktransfer.export.excel');
    Route::get('/stock-transfer/export-pdf', [\App\Http\Controllers\Erp\StockTransferController::class, 'exportPdf'])->name('stocktransfer.export.pdf');
    Route::get('/stock-transfer/{id}', [\App\Http\Controllers\Erp\StockTransferController::class, 'show'])->name('stocktransfer.show');
    Route::post('/stock-transfer', [\App\Http\Controllers\Erp\StockTransferController::class, 'store'])->name('stocktransfer.store');
    Route::patch('/stock-transfer/{id}/status', [\App\Http\Controllers\Erp\StockTransferController::class, 'updateStatus'])->name('stocktransfer.status');
    Route::get('/stock-transfer/{id}/return', [\App\Http\Controllers\Erp\StockTransferController::class, 'return'])->name('stocktransfer.return');
    Route::post('/stock-transfer/{id}/reconcile', [\App\Http\Controllers\Erp\StockTransferController::class, 'reconcile'])->name('stocktransfer.reconcile');
    Route::delete('/stock-transfer/{id}', [\App\Http\Controllers\Erp\StockTransferController::class, 'destroy'])->name('stocktransfer.destroy');
    Route::post('/stock-transfer/bulk-delete', [\App\Http\Controllers\Erp\StockTransferController::class, 'bulkDelete'])->name('stocktransfer.bulk.delete');

    // Requisitions
    Route::get('/requisition/export-excel', [\App\Http\Controllers\Erp\RequisitionController::class, 'exportExcel'])->name('requisition.export.excel');
    Route::get('/requisition/export-pdf', [\App\Http\Controllers\Erp\RequisitionController::class, 'exportPdf'])->name('requisition.export.pdf');
    Route::get('/requisition', [\App\Http\Controllers\Erp\RequisitionController::class, 'index'])->name('requisition.index');
    Route::get('/requisition/create', [\App\Http\Controllers\Erp\RequisitionController::class, 'create'])->name('requisition.create');
    Route::post('/requisition', [\App\Http\Controllers\Erp\RequisitionController::class, 'store'])->name('requisition.store');
    Route::get('/requisition/{id}/edit', [\App\Http\Controllers\Erp\RequisitionController::class, 'edit'])->name('requisition.edit');
    Route::put('/requisition/{id}', [\App\Http\Controllers\Erp\RequisitionController::class, 'update'])->name('requisition.update');
    Route::get('/requisition/{id}', [\App\Http\Controllers\Erp\RequisitionController::class, 'show'])->name('requisition.show');
    Route::post('/requisition/{id}/fulfill', [\App\Http\Controllers\Erp\RequisitionController::class, 'fulfill'])->name('requisition.fulfill');
    Route::delete('/requisition/{id}', [\App\Http\Controllers\Erp\RequisitionController::class, 'destroy'])->name('requisition.destroy');



    // Sale Return
    Route::get('/sale-return/export-excel', [\App\Http\Controllers\Erp\SaleReturnController::class, 'exportExcel'])->name('saleReturn.export.excel');
    Route::get('/sale-return/export-pdf', [\App\Http\Controllers\Erp\SaleReturnController::class, 'exportPdf'])->name('saleReturn.export.pdf');
    Route::get('/sale-return/search-invoice', [\App\Http\Controllers\Erp\SaleReturnController::class, 'searchInvoice'])->name('saleReturn.search.invoice');
    Route::get('/sale-return', [\App\Http\Controllers\Erp\SaleReturnController::class, 'index'])->name('saleReturn.list');
    Route::get('/sale-return/create', [\App\Http\Controllers\Erp\SaleReturnController::class, 'create'])->name('saleReturn.create');
    Route::post('/sale-return/store', [\App\Http\Controllers\Erp\SaleReturnController::class, 'store'])->name('saleReturn.store');
    Route::get('/sale-return/{id}', [\App\Http\Controllers\Erp\SaleReturnController::class, 'show'])->name('saleReturn.show');
    Route::get('/sale-return/{id}/edit', [\App\Http\Controllers\Erp\SaleReturnController::class, 'edit'])->name('saleReturn.edit');
    Route::put('/sale-return/{id}', [\App\Http\Controllers\Erp\SaleReturnController::class, 'update'])->name('saleReturn.update');
    Route::delete('/sale-return/{id}', [\App\Http\Controllers\Erp\SaleReturnController::class, 'destroy'])->name('saleReturn.delete');
    Route::post('/sale-return/{id}/update-status', [\App\Http\Controllers\Erp\SaleReturnController::class, 'updateReturnStatus'])->name('saleReturn.updateStatus');

    // Exchange
    Route::get('/exchange/export-excel', [\App\Http\Controllers\Erp\ExchangeController::class, 'exportExcel'])->name('exchange.export.excel');
    Route::get('/exchange/export-pdf', [\App\Http\Controllers\Erp\ExchangeController::class, 'exportPdf'])->name('exchange.export.pdf');
    Route::get('/exchange', [\App\Http\Controllers\Erp\ExchangeController::class, 'index'])->name('exchange.list');
    Route::get('/exchange/create', [\App\Http\Controllers\Erp\ExchangeController::class, 'create'])->name('exchange.create');
    Route::get('/exchange/latest-invoices', [\App\Http\Controllers\Erp\ExchangeController::class, 'latestInvoices'])->name('exchange.latest.invoices');
    Route::get('/exchange/search-invoice', [\App\Http\Controllers\Erp\ExchangeController::class, 'searchInvoice'])->name('exchange.search.invoice');
    Route::post('/exchange/store', [\App\Http\Controllers\Erp\ExchangeController::class, 'store'])->name('exchange.store');
    Route::get('/exchange/{id}', [\App\Http\Controllers\Erp\ExchangeController::class, 'show'])->name('exchange.show');
    Route::get('/exchange/{id}/print', [\App\Http\Controllers\Erp\ExchangeController::class, 'printReceipt'])->name('exchange.print');
    Route::delete('/exchange/{id}', [\App\Http\Controllers\Erp\ExchangeController::class, 'destroy'])->name('exchange.delete');

    // Purchase
    Route::get('/purchases', [\App\Http\Controllers\Erp\PurchaseController::class, 'index'])->name('purchase.list');
    Route::get('/purchases/export-excel', [\App\Http\Controllers\Erp\PurchaseController::class, 'exportExcel'])->name('purchase.export.excel');
    Route::get('/purchases/export-pdf', [\App\Http\Controllers\Erp\PurchaseController::class, 'exportPdf'])->name('purchase.export.pdf');
    Route::get('/purchases/create', [\App\Http\Controllers\Erp\PurchaseController::class, 'create'])->name('purchase.create');
    Route::post('/purchases', [\App\Http\Controllers\Erp\PurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/purchases/{id}', [\App\Http\Controllers\Erp\PurchaseController::class, 'show'])->name('purchase.show');
    Route::get('/purchases/{id}/edit', [\App\Http\Controllers\Erp\PurchaseController::class, 'edit'])->name('purchase.edit');
    Route::put('/purchases/{id}', [\App\Http\Controllers\Erp\PurchaseController::class, 'update'])->name('purchase.update');
    Route::delete('/purchases/{id}', [\App\Http\Controllers\Erp\PurchaseController::class, 'delete'])->name('purchase.delete');
    Route::put('/purchases/{id}/update-status', [\App\Http\Controllers\Erp\PurchaseController::class, 'updateStatus'])->name('purchase.updateStatus');
    Route::get('/purchases/search', [\App\Http\Controllers\Erp\PurchaseController::class, 'searchPurchase'])->name('purchase.search');
    Route::get('/purchases/{id}/items', [\App\Http\Controllers\Erp\PurchaseController::class, 'getItemByPurchase'])->name('purchase.items');

    // Purchase Return
    Route::get('/purchase-return/export-excel', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'exportExcel'])->name('purchaseReturn.export.excel');
    Route::get('/purchase-return/export-pdf', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'exportPdf'])->name('purchaseReturn.export.pdf');
    Route::get('/purchase-return', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'index'])->name('purchaseReturn.list');
    Route::get('/purchase-return/create', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'create'])->name('purchaseReturn.create');
    Route::get('/purchase-return/search-invoice', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'searchPurchaseByInvoice'])->name('purchaseReturn.search.invoice');
    Route::get('/purchase-return/search-invoice-detail', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'searchInvoice'])->name('purchaseReturn.search.invoice.detail');
    Route::get('/purchase-return/purchase/{purchaseId}/items', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'getPurchaseItems'])->name('purchaseReturn.purchase.items');
    Route::post('/purchase-return/store', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'store'])->name('purchaseReturn.store');
    Route::get('/purchase-return/search-product', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'searchProductForReturn'])->name('purchaseReturn.search.product');
    Route::get('/purchase-return/stock/{productId}/{fromId}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'getStockByType'])->name('purchaseReturn.stock');
    Route::get('/purchase-return/{id}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'show'])->name('purchaseReturn.show');
    Route::get('/purchase-return/{id}/edit', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'edit'])->name('purchaseReturn.edit');
    Route::put('/purchase-return/{id}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'update'])->name('purchaseReturn.update');
    Route::post('/purchase-return/{id}/update-status', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'updateReturnStatus'])->name('purchaseReturn.updateStatus');
    Route::delete('/purchase-return/{id}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'delete'])->name('purchaseReturn.delete');

    // Customer
    // Customers
    Route::get('/customers/export-excel', [\App\Http\Controllers\Erp\CustomerController::class, 'exportExcel'])->name('customers.export.excel');
    Route::get('/customers/export-pdf', [\App\Http\Controllers\Erp\CustomerController::class, 'exportPdf'])->name('customers.export.pdf');
    Route::get('/customers', [\App\Http\Controllers\Erp\CustomerController::class, 'index'])->name('customers.list');
    Route::post('/customers', [\App\Http\Controllers\Erp\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customer/{id}', [\App\Http\Controllers\Erp\CustomerController::class, 'show'])->name('customer.show');
    Route::get('/customer/{id}/edit', [\App\Http\Controllers\Erp\CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{id}', [\App\Http\Controllers\Erp\CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{id}', [\App\Http\Controllers\Erp\CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('/customers/make-premium/{id}', [\App\Http\Controllers\Erp\CustomerController::class, 'makePremium'])->name('customers.makePremium');
    Route::post('/customers/remove-premium/{id}', [\App\Http\Controllers\Erp\CustomerController::class, 'removePremium'])->name('customers.removePremium');
    Route::post('/customers/edit-notes/{id}', [\App\Http\Controllers\Erp\CustomerController::class, 'editNotes'])->name('customers.editNotes');
    Route::get('/customers/search', [\App\Http\Controllers\Erp\CustomerController::class, 'customerSearch'])->name('customers.search');
    Route::get('/customers/{id}/address', [\App\Http\Controllers\Erp\CustomerController::class, 'address'])->name('customers.address');

    Route::get('/pos', [\App\Http\Controllers\Erp\PosController::class, 'index'])->name('pos.list');
    Route::get('/pos/search', [\App\Http\Controllers\Erp\PosController::class, 'posSearch'])->name('pos.search');
    Route::get('/pos/create', [\App\Http\Controllers\Erp\PosController::class, 'addPos'])->name('pos.add');
    Route::post('/pos/store', [\App\Http\Controllers\Erp\PosController::class, 'makeSale'])->name('pos.store');

    Route::get('/pos/store/manual', [\App\Http\Controllers\Erp\PosController::class, 'manualSaleCreate'])->name('pos.manual.create');
    Route::post('/pos/store/manual', [\App\Http\Controllers\Erp\PosController::class, 'manualSaleStore'])->name('pos.manual.store');

    // POS Report Routes (must come before /pos/{id} to avoid route conflicts)
    Route::get('/pos/report-data', [\App\Http\Controllers\Erp\PosController::class, 'getReportData'])->name('pos.report.data');
    Route::get('/pos/export-excel', [\App\Http\Controllers\Erp\PosController::class, 'exportExcel'])->name('pos.export.excel');
    Route::get('/pos/export-pdf', [\App\Http\Controllers\Erp\PosController::class, 'exportPdf'])->name('pos.export.pdf');

    Route::get('/pos/{id}', [\App\Http\Controllers\Erp\PosController::class, 'show'])->name('pos.show');
    Route::get('/pos/{id}/details', [\App\Http\Controllers\Erp\PosController::class, 'getDetails'])->name('pos.details');
    Route::get('/pos/{id}/edit', [\App\Http\Controllers\Erp\PosController::class, 'edit'])->name('pos.edit');
    Route::post('/pos/{id}/update', [\App\Http\Controllers\Erp\PosController::class, 'update'])->name('pos.update');
    Route::get('/pos/{id}/print', [\App\Http\Controllers\Erp\PosController::class, 'print'])->name('pos.print');
    Route::get('/pos/product/{productId}/stock', [\App\Http\Controllers\Erp\PosController::class, 'getMultiBranchStock'])->name('pos.product.stock');
    Route::get('/pos/product/{productId}/variation/{variationId}/stock', [\App\Http\Controllers\Erp\PosController::class, 'getMultiBranchStock'])->name('pos.variation.stock');
    Route::get('/pos/product/{productId}/branch/{branchId}/stock/{variationId?}', [\App\Http\Controllers\Erp\PosController::class, 'getBranchStock'])->name('pos.product.branch.stock');
    Route::post('/pos/bulk-delete', [\App\Http\Controllers\Erp\PosController::class, 'bulkDestroy'])->name('pos.bulkDelete');
    Route::delete('/pos/{id}/delete', [\App\Http\Controllers\Erp\PosController::class, 'destroy'])->name('pos.delete');
    // Technician assignment route removed - not needed for ecommerce-only business
    // Route::post('/pos/assign-tech/{saleId}/{techId}', [\App\Http\Controllers\Erp\PosController::class, 'assignTechnician'])->name('pos.assign.tech');
    Route::post('/pos/update-note/{saleId}', [\App\Http\Controllers\Erp\PosController::class, 'updateNote'])->name('pos.update.note');
    Route::post('/pos/add-payment/{saleId}', [\App\Http\Controllers\Erp\PosController::class, 'addPayment'])->name('pos.add.payment');
    Route::post('/pos/update-status/{saleId}', [\App\Http\Controllers\Erp\PosController::class, 'updateStatus'])->name('pos.update.status');
    Route::post('/pos/add-address/{invoiceId}', [\App\Http\Controllers\Erp\PosController::class, 'addAddress'])->name('pos.add.address');

    // Add explicit routes for employees
    Route::get('/employees/search', [\App\Http\Controllers\Erp\EmployeeController::class, 'employeeSearch'])->name('employees.search');
    Route::get('/employees', [\App\Http\Controllers\Erp\EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [\App\Http\Controllers\Erp\EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [\App\Http\Controllers\Erp\EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [\App\Http\Controllers\Erp\EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [\App\Http\Controllers\Erp\EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [\App\Http\Controllers\Erp\EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [\App\Http\Controllers\Erp\EmployeeController::class, 'destroy'])->name('employees.destroy');

    // Invoice
    Route::get('/invoice-templates', [InvoiceController::class, 'templateList'])->name('invoice.template.list');
    Route::post('/invoice-templates', [InvoiceController::class, 'storeTemplate'])->name('invoice.template.store');
    Route::patch('/invoice-templates/{id}', [InvoiceController::class, 'updateTemplate'])->name('invoice.template.update');
    Route::delete('/invoice-templates/{id}', [InvoiceController::class, 'deleteTemplate'])->name('invoice.template.delete');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoice.list');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoice.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoice.store');

    // Invoice Report Routes
    Route::get('/invoices/report-data', [InvoiceController::class, 'getReportData'])->name('invoices.report.data');
    Route::get('/invoices/export-excel', [InvoiceController::class, 'exportExcel'])->name('invoices.export.excel');
    Route::get('/invoices/export-pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.export.pdf');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoice.show');
    Route::post('/invoices/add-payment/{id}', [InvoiceController::class, 'addPayment'])->name('invoice.addPayment');
    Route::get('/erp/invoices/{id}/edit', [InvoiceController::class, 'edit'])->name('invoice.edit');
    Route::patch('/erp/invoices/{id}', [InvoiceController::class, 'update'])->name('invoice.update');
    Route::post('/invoices/bulk-delete', [InvoiceController::class, 'bulkDestroy'])->name('invoice.bulkDelete');
    Route::delete('/invoices/{id}/delete', [InvoiceController::class, 'destroy'])->name('invoice.delete');




    // Order
    Route::get('/order-list', [\App\Http\Controllers\Erp\OrderController::class, 'index'])->name('order.list');
    Route::get('/order-list/export-excel', [\App\Http\Controllers\Erp\OrderController::class, 'exportExcel'])->name('order.export.excel');
    Route::get('/order-list/export-pdf', [\App\Http\Controllers\Erp\OrderController::class, 'exportPdf'])->name('order.export.pdf');
    Route::get('/order/search', [\App\Http\Controllers\Erp\OrderController::class, 'orderSearch'])->name('order.search');
    Route::get('/order/{id}/details', [\App\Http\Controllers\Erp\OrderController::class, 'show'])->name('order.details.api');
    Route::get('/order-list/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'show'])->name('order.show');
    Route::post('/order/set-estimated-delivery/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'setEstimatedDelivery'])->name('order.setEstimatedDelivery');
    Route::post('/order/update-estimated-delivery/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'updateEstimatedDelivery'])->name('order.updateEstimatedDelivery');
    Route::post('/order/update-status/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'updateStatus'])->name('order.updateStatus');
    Route::post('/order/update-technician/{id}/{employee_id}', [\App\Http\Controllers\Erp\OrderController::class, 'updateTechnician'])->name('order.updateTechnician');
    Route::post('/order/remove-technician/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'deleteTechnician'])->name('order.deleteTechnician');
    Route::post('/order/update-note/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'updateNote'])->name('order.updateNote');
    Route::post('/order/add-payment/{orderId}', [\App\Http\Controllers\Erp\OrderController::class, 'addPayment'])->name('order.add.payment');
    Route::get('/order/product-stocks/{productId}', [\App\Http\Controllers\Erp\OrderController::class, 'getProductStocks'])->name('order.productStocks');
    Route::post('/order/product-stock-add/{orderId}', [\App\Http\Controllers\Erp\OrderController::class, 'addStockToOrderItem'])->name('order.addStockToOrderItem');
    Route::post('/order/transfer-stock-to-employee/{orderItemId}', [\App\Http\Controllers\Erp\OrderController::class, 'transferStockToEmployee'])->name('order.transferStockToEmployee');
    Route::delete('/order/{id}', [\App\Http\Controllers\Erp\OrderController::class, 'destroy'])->name('erp.order.delete');


    // Order Return
    Route::get('/order-return', [\App\Http\Controllers\Erp\OrderReturnController::class, 'index'])->name('orderReturn.list');
    Route::get('/order-return/export-excel', [\App\Http\Controllers\Erp\OrderReturnController::class, 'exportExcel'])->name('orderReturn.export.excel');
    Route::get('/order-return/export-pdf', [\App\Http\Controllers\Erp\OrderReturnController::class, 'exportPdf'])->name('orderReturn.export.pdf');
    Route::get('/order-return/create', [\App\Http\Controllers\Erp\OrderReturnController::class, 'create'])->name('orderReturn.create');
    Route::post('/order-return/store', [\App\Http\Controllers\Erp\OrderReturnController::class, 'store'])->name('orderReturn.store');
    Route::get('/order-return/{id}', [\App\Http\Controllers\Erp\OrderReturnController::class, 'show'])->name('orderReturn.show');
    Route::get('/order-return/{id}/edit', [\App\Http\Controllers\Erp\OrderReturnController::class, 'edit'])->name('orderReturn.edit');
    Route::put('/order-return/{id}', [\App\Http\Controllers\Erp\OrderReturnController::class, 'update'])->name('orderReturn.update');
    Route::delete('/order-return/{id}', [\App\Http\Controllers\Erp\OrderReturnController::class, 'destroy'])->name('orderReturn.delete');
    Route::post('/order-return/{id}/update-status', [\App\Http\Controllers\Erp\OrderReturnController::class, 'updateReturnStatus'])->name('orderReturn.updateStatus');

    // Order Exchange
    Route::get('/order-exchange', [\App\Http\Controllers\Erp\OrderExchangeController::class, 'index'])->name('orderExchange.list');
    Route::get('/order-exchange/export', [\App\Http\Controllers\Erp\OrderExchangeController::class, 'exportExcel'])->name('orderExchange.export');
    Route::get('/order-exchange/create', [\App\Http\Controllers\Erp\OrderExchangeController::class, 'create'])->name('orderExchange.create');
    Route::post('/order-exchange/store', [\App\Http\Controllers\Erp\OrderExchangeController::class, 'store'])->name('orderExchange.store');
    Route::get('/order-exchange/{id}', [\App\Http\Controllers\Erp\OrderExchangeController::class, 'show'])->name('orderExchange.show');

    // Customer Services functionality disabled - commented out
    // Route::get('/customer-services/search', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'search'])->name('customerService.search');
    // Route::get('/customer-services', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'index'])->name('customerService.list');
    // Route::get('/customer-services/create', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'create'])->name('customerService.create');
    // Route::post('/customer-services/store', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'store'])->name('service.store');
    // Route::get('/customer-services/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'show'])->name('customerService.show');
    // Route::post('/customer-services/update-technician/{id}/{employee_id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateTechnician'])->name('customerService.updateTechnician');
    // Route::post('/customer-services/remove-technician/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'deleteTechnician'])->name('customerService.deleteTechnician');
    // Route::post('/customer-services/product-stock-add/{serviceId}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addStockToServiceItem'])->name('customerService.addStockToServiceItem');
    // Route::post('/customer-services/transfer-stock-to-employee/{serviceId}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'transferStockToEmployee'])->name('customerService.transferStockToEmployee');
    // Route::post('/customer-services/add-payment/{serviceId}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addPayment'])->name('customerService.add.payment');
    // Route::post('/customer-services/update-note/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateNote'])->name('customerService.updateNote');
    // Route::post('/customer-services/add-address/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addAddress'])->name('customerService.addAddress');
    // Route::post('/customer-services/update-status/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateStatus'])->name('customerService.updateStatus');
    // Route::post('/customer-services/add-extra-part', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addExtraPart'])->name('customerService.addExtraPart');
    // Route::post('/customer-services/delete-extra-part', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'deleteExtraPart'])->name('customerService.deleteExtraPart');
    // Route::post('/customer-services/update-service-fees', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateServiceFees'])->name('customerService.updateServiceFees');

    // Simple Accounting Routes
    Route::get('/simple-accounting/sales-summary', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'salesSummary'])->name('simple-accounting.sales-summary');
    Route::get('/simple-accounting/sales-report', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'salesReport'])->name('simple-accounting.sales-report');
    Route::get('/simple-accounting/top-products', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'topProducts'])->name('simple-accounting.top-products');
    Route::get('/simple-accounting/stock-value', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'stockValue'])->name('simple-accounting.stock-value');
    Route::get('/simple-accounting/get-sales-report-data', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'getSalesDataReport'])->name('simple-accounting.get-sales-report-data');
    Route::get('/simple-accounting/export-excel', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportExcel'])->name('simple-accounting.export-excel');
    Route::get('/simple-accounting/export-pdf', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportPdf'])->name('simple-accounting.export-pdf');
    Route::get('/simple-accounting/summary-export-excel', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportSummaryExcel'])->name('simple-accounting.summary-export-excel');
    Route::get('/simple-accounting/summary-export-pdf', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportSummaryPdf'])->name('simple-accounting.summary-export-pdf');
    Route::get('/simple-accounting/top-products-export-excel', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportTopProductsExcel'])->name('simple-accounting.top-products-export-excel');
    Route::get('/simple-accounting/top-products-export-pdf', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportTopProductsPdf'])->name('simple-accounting.top-products-export-pdf');
    Route::get('/simple-accounting/stock-export-excel', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportStockExcel'])->name('simple-accounting.stock-export-excel');
    Route::get('/simple-accounting/stock-export-pdf', [\App\Http\Controllers\Erp\SimpleAccountingController::class, 'exportStockPdf'])->name('simple-accounting.stock-export-pdf');

    // Money Receipt
    Route::get('/money-receipt/export-excel', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'exportExcel'])->name('money-receipt.export.excel');
    Route::get('/money-receipt/export-pdf', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'exportPdf'])->name('money-receipt.export.pdf');
    Route::get('/money-receipt', [\App\Http\Controllers\Erp\MoneyReceiptController::class, 'index'])->name('money-receipt.index');

    // User Role
    Route::get('/user-role', [\App\Http\Controllers\Erp\UserRoleController::class, 'index'])->name('userRole.index');
    Route::post('/user-role', [\App\Http\Controllers\Erp\UserRoleController::class, 'store'])->name('userRole.store');
    Route::put('/user-role/{id}', [\App\Http\Controllers\Erp\UserRoleController::class, 'update'])->name('userRole.update');
    Route::delete('/user-role/{id}', [\App\Http\Controllers\Erp\UserRoleController::class, 'destroy'])->name('userRole.destroy');

    // Vlogging
    Route::get('/vlogging', [\App\Http\Controllers\Erp\VloggingController::class, 'index'])->name('vlogging.index');
    Route::post('/vlogging/store', [\App\Http\Controllers\Erp\VloggingController::class, 'store'])->name('vlogging.store');
    Route::patch('/vlogging/{vlog}', [\App\Http\Controllers\Erp\VloggingController::class, 'update'])->name('vlogging.update');
    Route::delete('/vlogging/{vlog}', [\App\Http\Controllers\Erp\VloggingController::class, 'destroy'])->name('vlogging.destroy');

    // Additional Page
    Route::get('/additional-pages', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'index'])->name('additionalPages.index');
    Route::get('/additional-pages/create', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'create'])->name('additionalPages.create');
    Route::post('/additional-pages/store', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'store'])->name('additionalPages.store');
    Route::get('/additional-pages/{id}', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'show'])->name('additionalPages.show');
    Route::get('/additional-pages/{id}/edit', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'edit'])->name('additionalPages.edit');
    Route::put('/additional-pages/{id}', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'update'])->name('additionalPages.update');
    Route::delete('/additional-pages/{id}', [\App\Http\Controllers\Erp\AdditionalPageController::class, 'destroy'])->name('additionalPages.destroy');

    // Attributes
    Route::get('/attributes', [\App\Http\Controllers\Erp\AttributeController::class, 'index'])->name('attribute.list');
    Route::get('/attributes/create', [\App\Http\Controllers\Erp\AttributeController::class, 'create'])->name('attribute.create');
    Route::post('/attributes/store', [\App\Http\Controllers\Erp\AttributeController::class, 'store'])->name('attribute.store');
    Route::get('/attributes/{id}', [\App\Http\Controllers\Erp\AttributeController::class, 'show'])->name('attribute.show');
    Route::get('/attributes/{id}/edit', [\App\Http\Controllers\Erp\AttributeController::class, 'edit'])->name('attribute.edit');
    Route::put('/attributes/{id}', [\App\Http\Controllers\Erp\AttributeController::class, 'update'])->name('attribute.update');
    Route::delete('/attributes/{id}', [\App\Http\Controllers\Erp\AttributeController::class, 'destroy'])->name('attribute.destroy');
    
    // Settings
    Route::get('/settings', [\App\Http\Controllers\Erp\GeneralSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Erp\GeneralSettingsController::class, 'storeUpdate'])->name('settings.update');
    Route::post('/admin/test-smtp', [\App\Http\Controllers\Erp\GeneralSettingsController::class, 'testSmtp'])->name('admin.test.smtp');
    
    
    // Shipping Methods
    Route::resource('shipping-methods', \App\Http\Controllers\Erp\ShippingMethodController::class);

    // Financial Accounts (Bank / Mobile / Cash)
    Route::get('/financial-accounts', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'index'])->name('financial-accounts.index');
    Route::post('/financial-accounts', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'store'])->name('financial-accounts.store');
    Route::put('/financial-accounts/{id}', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'update'])->name('financial-accounts.update');
    Route::delete('/financial-accounts/{id}', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'destroy'])->name('financial-accounts.destroy');
    Route::get('/api/financial-accounts', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'getAll'])->name('financial-accounts.all');

    // Journal Entry Actions (Line Items)
    Route::get('/journal-entry/{id}', [JournalController::class, 'showEntry'])->name('journal.entry.show');
    Route::put('/journal-entry/{id}', [JournalController::class, 'updateEntry'])->name('journal.entry.update');
    Route::delete('/journal-entry/{id}', [JournalController::class, 'destroyEntry'])->name('journal.entry.destroy');

    // Double Entry Accounting
    Route::prefix('double-entry')->group(function () {
        // Chart of Accounts
        Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart-of-account.index');
        Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('chart-of-account.store');
        Route::put('/chart-of-accounts/{id}', [ChartOfAccountController::class, 'update'])->name('chart-of-account.update');
        Route::delete('/chart-of-accounts/{id}', [ChartOfAccountController::class, 'destroy'])->name('chart-of-account.destroy');
        
        Route::post('/chart-of-account-parents', [ChartOfAccountController::class, 'storeParent'])->name('chart-of-account.parent.store');
        Route::put('/chart-of-account-parents/{id}', [ChartOfAccountController::class, 'updateParent'])->name('chart-of-account.parent.update');
        Route::delete('/chart-of-account-parents/{id}', [ChartOfAccountController::class, 'destroyParent'])->name('chart-of-account.parent.destroy');

        Route::post('/account-types', [ChartOfAccountController::class, 'storeType'])->name('account-type.store');
        Route::get('/get-sub-types/{id}', [ChartOfAccountController::class, 'getSubTypes']);
        Route::get('/get-next-code/{typeId}', [ChartOfAccountController::class, 'getNextCode']);

        // Journals (Vouchers)
        Route::get('/journals', [JournalController::class, 'index'])->name('journal.list');
        Route::get('/journals/export-excel', [JournalController::class, 'exportExcel'])->name('journal.export.excel');
        Route::get('/journals/export-pdf', [JournalController::class, 'exportPdf'])->name('journal.export.pdf');
        Route::post('/journals', [JournalController::class, 'store'])->name('journal.store');
        Route::get('/journals/{id}', [JournalController::class, 'show'])->name('journal.show');
        Route::put('/journals/{id}', [JournalController::class, 'update'])->name('journal.update');
        Route::delete('/journals/{id}', [JournalController::class, 'destroy'])->name('journal.destroy');
        Route::post('/journals/{id}/entries', [JournalController::class, 'storeEntry'])->name('journal.entry.store');

        // New Vouchers (Simplified UI)
        Route::get('/vouchers', [\App\Http\Controllers\Erp\VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [\App\Http\Controllers\Erp\VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [\App\Http\Controllers\Erp\VoucherController::class, 'store'])->name('vouchers.store');
        Route::delete('/vouchers/{id}', [\App\Http\Controllers\Erp\VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/export-excel', [\App\Http\Controllers\Erp\VoucherController::class, 'exportExcel'])->name('vouchers.export.excel');
        Route::get('/vouchers/export-pdf', [\App\Http\Controllers\Erp\VoucherController::class, 'exportPdf'])->name('vouchers.export.pdf');
        
        // Accounting Reports
        Route::get('/ledger', [DoubleEntryReportController::class, 'ledgerIndex'])->name('ledger.index');
        Route::get('/ledger/{id}', [DoubleEntryReportController::class, 'ledgerAccount'])->name('ledger.account');
        Route::get('/trial-balance', [DoubleEntryReportController::class, 'trialBalance'])->name('trialBalance.index');
        Route::get('/balance-sheet', [DoubleEntryReportController::class, 'balanceSheet'])->name('balanceSheet.index');
        Route::get('/profit-loss', [DoubleEntryReportController::class, 'profitLoss'])->name('profitLoss.index');
    });

    // Alias routes if views expect them at root ERP
    Route::get('/erp/chart-of-account', [ChartOfAccountController::class, 'index']);
    Route::get('/erp/journal', [JournalController::class, 'index']);
});
Route::get('/wishlist/count', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'wishlistCount'])->name('wihslist.count');
Route::post('/add-remove-wishlist/{productId}', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'addToWishlist'])->name('wishlist.add');

Route::get('/api/products/most-sold', [\App\Http\Controllers\Ecommerce\ApiController::class, 'mostSoldProducts']);
Route::get('/api/products/new-arrivals', [\App\Http\Controllers\Ecommerce\ApiController::class, 'newArrivalsProducts']);
Route::get('/api/products/best-deals', [\App\Http\Controllers\Ecommerce\ApiController::class, 'bestDealsProducts']);
// Cart routes - require authentication (handled in controller)
Route::post('/cart/add/{productId}', [App\Http\Controllers\Ecommerce\CartController::class, 'addToCartByCard'])->name('cart.addByCard');
Route::post('/cart/add-page/{productId}', [App\Http\Controllers\Ecommerce\CartController::class, 'addToCartByPage'])->name('cart.addByPage');
Route::get('/cart/qty-sum', [App\Http\Controllers\Ecommerce\CartController::class, 'getCartQtySum'])->name('cart.qtySum');
Route::get('/cart/list', [App\Http\Controllers\Ecommerce\CartController::class, 'getCartList'])->name('cart.list');
Route::post('/cart/increase/{cartId}', [App\Http\Controllers\Ecommerce\CartController::class, 'increaseQuantity'])->name('cart.increase');
Route::post('/cart/decrease/{cartId}', [App\Http\Controllers\Ecommerce\CartController::class, 'decreaseQuantity'])->name('cart.decrease');
Route::delete('/cart/delete/{cartId}', [App\Http\Controllers\Ecommerce\CartController::class, 'deleteCartItem'])->name('cart.delete');
Route::post('/buy-now/{productId}', [App\Http\Controllers\Ecommerce\CartController::class, 'buyNow'])->name('buyNow');

// Test Email Route (for development only)
// Route::get('/test-email/{orderId}', function ($orderId) {
//     $order = \App\Models\Order::with(['items.product', 'customer'])->find($orderId);
//     if (!$order) {
//         return 'Order not found';
//     }

//     try {
//         \Illuminate\Support\Facades\Mail::to('test@example.com')->send(new \App\Mail\OrderConfirmation($order));
//         return 'Email sent successfully!';
//     } catch (\Exception $e) {
//         return 'Email failed: ' . $e->getMessage();
//     }
// })->name('test.email');

// Test Sale Email Route (for development only)
// Route::get('/test-sale-email/{posId}', function ($posId) {
//     $pos = \App\Models\Pos::with(['items.product', 'customer', 'payments', 'employee.user', 'soldBy'])->find($posId);
//     if (!$pos) {
//         return 'POS Sale not found';
//     }

//     try {
//         \Illuminate\Support\Facades\Mail::to('test@example.com')->send(new \App\Mail\SaleConfirmation($pos));
//         return 'Sale confirmation email sent successfully!';
//     } catch (\Exception $e) {
//         return 'Email failed: ' . $e->getMessage();
//     }
// })->name('test.sale.email');

// Test Contact Email Route (for development only - remove in production)
// Route::get('/test-contact-email', function () {
//     // Configure SMTP from admin settings
//     \App\Services\SmtpConfigService::configureFromSettings();
//     
//     $contactData = [
//         'full_name' => 'Test User',
//         'phone_number' => '+1234567890',
//         'subject' => 'Test Contact Form',
//         'message' => 'This is a test message from the contact form.',
//         'submitted_at' => now(),
//     ];
//
//     // Get contact email from settings
//     $contactEmail = \App\Services\SmtpConfigService::getContactEmail();
//     
//     // Show configuration details
//     $config = [
//         'smtp_configured' => \App\Services\SmtpConfigService::isConfigured(),
//         'smtp_host' => config('mail.mailers.smtp.host'),
//         'smtp_port' => config('mail.mailers.smtp.port'),
//         'smtp_username' => config('mail.mailers.smtp.username'),
//         'from_address' => config('mail.from.address'),
//         'from_name' => config('mail.from.name'),
//         'contact_email' => $contactEmail,
//     ];
//
//     try {
//         \Illuminate\Support\Facades\Mail::to($contactEmail)->send(new \App\Mail\ContactMail($contactData));
//         return response()->json([
//             'success' => true,
//             'message' => 'Contact email sent successfully!',
//             'config' => $config
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Email failed: ' . $e->getMessage(),
//             'config' => $config
//         ]);
//     }
// })->name('test.contact.email');

// Route::get('/sync-migrations', function () {
//     try {
//         $migrationsToMark = [
//             '2025_11_09_084537_add_type_and_value_to_bulk_discounts_table',
//             '2025_11_10_085613_add_free_delivery_to_coupons_table',
//             '2025_11_10_093510_add_cod_percentage_to_general_settings_table',
//             '2025_11_12_110836_add_features_to_products_table',
//             '2025_11_12_115011_add_free_delivery_to_products_table',
//             '2025_11_12_120257_add_free_delivery_to_bulk_discounts_table',
//             '2025_11_12_122248_update_bulk_discounts_type_enum_to_include_free_delivery',
//             '2025_11_13_054921_add_gtm_container_id_to_general_settings_table',
//             '2025_12_24_051629_add_is_ecommerce_to_products_table',
//             '2026_01_13_052907_create_brands_table',
//             '2026_01_13_052908_create_seasons_table',
//             '2026_01_13_052908_create_genders_table',
//             '2026_01_13_052909_create_units_table',
//             '2026_01_13_053115_add_extra_fields_to_products_table',
//             '2026_01_13_103942_add_manual_sale_fields_to_pos_table',
//             '2026_01_19_090950_add_discount_to_invoice_items_table',
//             '2025_01_20_000000_add_variation_id_to_sale_return_items_table',
//             '2025_01_20_000001_add_variation_id_to_stock_transfers_table',
//             '2025_01_27_000000_add_telegram_username_to_general_settings_table',
//             '2026_01_25_103639_add_status_and_show_online_to_branches_table',
//         ];

//         $output = "<h2>Migration Sync Report</h2>";
//         $batch = DB::table('migrations')->max('batch') + 1;
//         $marked = 0;
//         $skipped = 0;

//         foreach ($migrationsToMark as $migration) {
//             $exists = DB::table('migrations')->where('migration', $migration)->exists();
            
//             if (!$exists) {
//                 DB::table('migrations')->insert([
//                     'migration' => $migration,
//                     'batch' => $batch
//                 ]);
//                 $output .= "<div style='color: green;'>✓ Marked as complete: {$migration}</div>";
//                 $marked++;
//             } else {
//                 $output .= "<div style='color: gray;'>○ Already tracked: {$migration}</div>";
//                 $skipped++;
//             }
        
//         // Allow specifying user ID via URL parameter
//         if ($request->has('user_id')) {
//             $output .= "<div>Looking for user with ID {$request->user_id}...</div>";
//             $adminUsers = DB::table('users')->where('id', $request->user_id)->get();
//             $output .= "<div>Found " . $adminUsers->count() . " user(s)</div><br>";
//         } else {
//             $output .= "<div>Auto-detecting admin users...</div>";
//             // Get currently logged in user or all users with 'admin' in email
//             $adminUsers = DB::table('users')->where('email', 'like', '%admin%')->get();
            
//             if ($adminUsers->isEmpty()) {
//                 $output .= "<div>No admin emails found, getting first user...</div>";
//                 // If no admin emails found, just get the first user (likely the owner)
//                 $adminUsers = DB::table('users')->orderBy('id')->limit(1)->get();
//             }
//             $output .= "<div>Found " . $adminUsers->count() . " user(s)</div><br>";
//         }
        
//         if ($adminUsers->isEmpty()) {
//             return $output . "<div style='color: red;'>❌ No users found. Please specify user ID in URL: /fix-permissions?user_id=18</div>";
//         }

//         // Check if permissions table exists
//         $output .= "<div>Checking if permissions table exists...</div>";
//         if (!Schema::hasTable('permissions')) {
//             $output .= "<div style='color: orange;'>⚠️ Permissions table doesn't exist.</div>";
//             $output .= "<div style='color: green;'>✓ Your app doesn't use Spatie role-based permissions.</div>";
//             $output .= "<br><strong>Users found:</strong><br>";
//             foreach ($adminUsers as $user) {
//                 $output .= "<div>• {$user->name} ({$user->email})</div>";
//             }
//             $output .= "<br><div style='color: blue;'>ℹ️ The 500 errors are NOT from missing permissions. Check your Laravel logs for the actual error.</div>";
//             return $output;
//         }
        
//         $output .= "<div style='color: green;'>✓ Permissions table exists</div><br>";

//         // Get all permissions
//         $output .= "<div>Loading all permissions...</div>";
//         $permissions = DB::table('permissions')->get();
//         $output .= "<div>Found {$permissions->count()} permissions in database</div><br>";
        
//         if ($permissions->isEmpty()) {
//             $output .= "<div style='color: orange;'>⚠️ No permissions found in database. Run: php artisan db:seed --class=PermissionSeeder</div>";
//             return $output;
//         }
        
//         foreach ($adminUsers as $user) {
//             $output .= "<br><strong>Processing: {$user->name} ({$user->email}) [ID: {$user->id}]</strong><br>";
//             $granted = 0;
//             $skipped = 0;
            
//             foreach ($permissions as $permission) {
//                 $exists = DB::table('model_has_permissions')
//                     ->where('permission_id', $permission->id)
//                     ->where('model_type', 'App\\Models\\User')
//                     ->where('model_id', $user->id)
//                     ->exists();
                
//                 if (!$exists) {
//                     try {
//                         DB::table('model_has_permissions')->insert([
//                             'permission_id' => $permission->id,
//                             'model_type' => 'App\\Models\\User',
//                             'model_id' => $user->id
//                         ]);
//                         $granted++;
//                     } catch (\Exception $e) {
//                         // Skip if already exists (duplicate key error)
//                         $skipped++;
//                     }
//                 } else {
//                     $skipped++;
//                 }
//             }
            
//             $output .= "<div style='color: green;'>✓ Granted {$granted} new permissions</div>";
//             $output .= "<div style='color: gray;'>○ Already had {$skipped} permissions</div>";
//         }

//         $output .= "<br><strong>✅ Permission sync complete!</strong><br>";
//         $output .= "<a href='/erp/dashboard' style='padding: 10px 20px; background: #198754; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; display: inline-block;'>Go to Dashboard</a>";
        
//         return $output;
//     } catch (\Exception $e) {
//         return "<h2>Error Details</h2>" . 
//                "<div style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</div>" .
//                "<br><strong>File:</strong> " . $e->getFile() . 
//                "<br><strong>Line:</strong> " . $e->getLine() .
//                "<br><br><strong>Stack trace:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
//     }
// });


// Route::get('/migrate-db', function() {
//     try {
//         Artisan::call('migrate', ['--force' => true]);
//         return '<h1>Migration Completed Successfully!</h1><br><pre>' . Artisan::output() . '</pre>';
//     } catch (\Exception $e) {
//         return '<h1>Migration Failed</h1><br><pre>' . $e->getMessage() . '</pre>';
//     }
// });



// Route to run migrations from web
Route::get('/run-migrate', function() {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return '<h1>Migration Completed Successfully!</h1><br><pre>' . Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return '<h1>Migration Failed</h1><br><pre>' . $e->getMessage() . '</pre>';
    }
});

Route::get('/run-perm-fix', function() {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
    return "Permissions Fixed! Output: <pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
})->middleware(['auth', 'admin']);



// Route to run Permission Seeder from web
// Route::get('/run-seed', function() {
//     try {
//         // Run the seeder
//         Artisan::call('db:seed', [
//             '--class' => 'PermissionSeeder',
//             '--force' => true
//         ]);
//         $output = Artisan::output();

//         // Clear all caches to ensure permissions reflect immediately
//         Artisan::call('optimize:clear');
//         $output .= "\n" . Artisan::output();

//         return '<h1>Permissions Seeded & Cache Cleared Successfully!</h1><br><pre>' . $output . '</pre>';
//     } catch (\Exception $e) {
//         return '<h1>Maintenance Failed</h1><br><pre>' . $e->getMessage() . '</pre>';
//     }
// });



// Specific fix for POS table if migrations are out of sync
// Route::get('/fix-pos-columns', function() {
//     try {
//         Schema::table('pos', function (Blueprint $table) {
//             if (!Schema::hasColumn('pos', 'challan_number')) {
//                 $table->string('challan_number')->nullable()->after('sale_number');
//             }
//             if (!Schema::hasColumn('pos', 'sale_type')) {
//                 $table->string('sale_type')->nullable()->after('sale_date');
//             }
//             if (!Schema::hasColumn('pos', 'account_type')) {
//                 $table->string('account_type')->nullable()->after('notes');
//             }
//             if (!Schema::hasColumn('pos', 'account_number')) {
//                 $table->string('account_number')->nullable()->after('account_type');
//             }
//             if (!Schema::hasColumn('pos', 'remarks')) {
//                 $table->text('remarks')->nullable()->after('account_number');
//             }
//             if (!Schema::hasColumn('pos', 'courier_id')) {
//                 $table->unsignedBigInteger('courier_id')->nullable()->after('remarks');
//             }
//         });
//         return '<h1>POS Table Columns Fixed Successfully!</h1>';
//     } catch (\Exception $e) {
//         return '<h1>Fix Failed</h1><br><pre>' . $e->getMessage() . '</pre>';
//     }

// Route::get('/clearallcustomers', function () {
//     if (!auth()->check() || !(auth()->user()->hasRole('Super Admin') || auth()->user()->id == 18)) {
//         abort(403, 'Unauthorized');
//     }
//     if (request('token') !== 'sisal_solve_2026') {
//         return 'Invalid token. Usage: /clearallcustomers?token=sisal_solve_2026';
//     }

//     DB::beginTransaction();
//     try {
//         // Delete all customers
//         DB::table('customers')->delete();

//         DB::commit();
//         return '<h1>All customers cleared successfully!</h1>';
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return '<h1>Clear customers failed!</h1><br><pre>' . $e->getMessage() . '</pre>';
//     }
// });

Route::get('/clearallstocks', function () {
    if (!auth()->check() || !(auth()->user()->hasRole('Super Admin') || auth()->user()->id == 18)) {
        abort(403, 'Unauthorized');
    }
    if (request('token') !== 'sisal_solve_2026') {
        return 'Invalid token. Usage: /clearallstocks?token=sisal_solve_2026';
    }

    DB::beginTransaction();
    try {
        // Delete all stock records
        DB::table('product_variation_stocks')->delete();
        DB::table('branch_product_stocks')->delete();
        DB::table('warehouse_product_stocks')->delete();

        // Clear cache for all products
        $productIds = \App\Models\Product::pluck('id');
        foreach ($productIds as $id) {
            \App\Services\CacheService::clearProductCaches($id);
        }

        DB::commit();
        return '<h1>All product variation, branch, and warehouse stocks reset to 0/deleted successfully! Cache cleared.</h1>';
    } catch (\Exception $e) {
        DB::rollBack();
        return '<h1>Stock reset failed!</h1><br><pre>' . $e->getMessage() . '</pre>';
    }
});

// Route::get('/clearadjustments', function () {
//     if (!auth()->check() || !(auth()->user()->hasRole('Super Admin') || auth()->user()->id == 18)) {
//         abort(403, 'Unauthorized');
//     }
//     if (request('token') !== 'sisal_solve_2026') {
//         return 'Invalid token. Usage: /clearadjustments?token=sisal_solve_2026';
//     }

//     DB::beginTransaction();
//     try {
//         $adjustments = \App\Models\StockAdjustment::with('items')->get();
//         $count = 0;

//         foreach ($adjustments as $adjustment) {
//             foreach ($adjustment->items as $item) {
//                 // Calculate the difference this adjustment applied
//                 $diff = $item->new_quantity - $item->old_quantity;

//                 if ($item->variation_id) {
//                     // --- Variation stock ---
//                     if ($adjustment->branch_id) {
//                         $stock = \App\Models\ProductVariationStock::where('variation_id', $item->variation_id)
//                             ->where('branch_id', $adjustment->branch_id)
//                             ->whereNull('warehouse_id')
//                             ->first();
//                     } else {
//                         $stock = \App\Models\ProductVariationStock::where('variation_id', $item->variation_id)
//                             ->where('warehouse_id', $adjustment->warehouse_id)
//                             ->whereNull('branch_id')
//                             ->first();
//                     }

//                     if ($stock) {
//                         $stock->quantity = max(0, $stock->quantity - $diff);
//                         $stock->updated_by = auth()->id() ?? 1;
//                         $stock->last_updated_at = now();
//                         $stock->save();
//                     }
//                 } else {
//                     // --- Product-level stock ---
//                     if ($adjustment->branch_id) {
//                         $stock = \App\Models\BranchProductStock::where('product_id', $item->product_id)
//                             ->where('branch_id', $adjustment->branch_id)
//                             ->first();
//                     } else {
//                         $stock = \App\Models\WarehouseProductStock::where('product_id', $item->product_id)
//                             ->where('warehouse_id', $adjustment->warehouse_id)
//                             ->first();
//                     }

//                     if ($stock) {
//                         $stock->quantity = max(0, $stock->quantity - $diff);
//                         $stock->updated_by = auth()->id() ?? 1;
//                         $stock->last_updated_at = now();
//                         $stock->save();
//                     }
//                 }

//                 // Clear product cache
//                 \App\Services\CacheService::clearProductCaches($item->product_id);
//             }

//             // Delete items and the adjustment record
//             $adjustment->items()->delete();
//             $adjustment->delete();
//             $count++;
//         }

//         DB::commit();
//         return "<h1>Successfully deleted {$count} stock adjustments and reverted their stocks!</h1>";
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return '<h1>Clear adjustments failed!</h1><br><pre>' . $e->getMessage() . '</pre>';
//     }
// });

Route::get('/orphandatasolve', function () {
    if (!auth()->check() || !(auth()->user()->hasRole('Super Admin') || auth()->user()->id == 18)) {
        abort(403, 'Unauthorized');
    }
    if (request('token') !== 'sisal_solve_2026') {
        return 'Invalid token. Usage: /orphandatasolve?token=sisal_solve_2026';
    }

    DB::beginTransaction();
    try {
        // Delete related journals and journal entries
        DB::table('journal_entries')->delete();
        DB::table('journals')->delete();

        // Delete all ledger and balance records
        DB::table('supplier_ledgers')->delete();
        DB::table('balances')->delete();

        // Delete payment logs
        DB::table('supplier_payments')->delete();
        DB::table('payments')->delete();

        // Delete any remaining orphan sales/purchases/returns
        DB::table('pos_items')->delete();
        DB::table('pos')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('pos_exchange_items')->delete();
        DB::table('pos_exchanges')->delete();
        DB::table('sale_return_items')->delete();
        DB::table('sale_returns')->delete();

        DB::table('purchase_items')->delete();
        DB::table('purchases')->delete();
        DB::table('purchase_bills')->delete();
        DB::table('purchase_return_items')->delete();
        DB::table('purchase_returns')->delete();

        // Reset all financial accounts balance to 0
        DB::table('financial_accounts')->update(['balance' => 0]);

        DB::commit();
        return '<h1>Orphan transactions and accounting data solved successfully! All cleared.</h1>';
    } catch (\Exception $e) {
        DB::rollBack();
        return '<h1>Cleanup failed!</h1><br><pre>' . $e->getMessage() . '</pre>';
    }
});

// Route::get('/recalculate-balances-script', function () {
//     if (request('token') !== 'sisal_solve_2026') {
//         return 'Invalid token. Usage: /recalculate-balances-script?token=sisal_solve_2026';
//     }

//     try {
//         $accounts = \App\Models\FinancialAccount::all();
//         $output = "=== Recalculating Financial Account Balances ===\n\n";

//         foreach ($accounts as $account) {
//             $movement = \App\Models\JournalEntry::where('financial_account_id', $account->id)
//                 ->selectRaw('SUM(debit) as d, SUM(credit) as c')
//                 ->first();

//             $debit = (float) ($movement->d ?? 0.0);
//             $credit = (float) ($movement->c ?? 0.0);
//             $newBalance = $debit - $credit;

//             $account->update(['balance' => $newBalance]);

//             $output .= "Account #{$account->id} ({$account->provider_name} - {$account->account_number}): Set balance to {$newBalance} (Debit: {$debit}, Credit: {$credit})\n";
//         }

//         return '<pre>' . $output . "\n\nRecalculation complete!</pre>";
//     } catch (\Exception $e) {
//         return 'Error: ' . $e->getMessage();
//     }
// });

require __DIR__ . '/auth.php';