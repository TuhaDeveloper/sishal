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
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/', [PageController::class, 'index'])->name('ecommerce.home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/products', [PageController::class, 'products'])->name('product.archive');
Route::post('/products/filter', [PageController::class, 'filterProducts'])->name('products.filter');
Route::get('/product/{slug}', [PageController::class, 'productDetails'])->name('product.details');

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

Route::get('/categories', [PageController::class, 'categories'])->name('categories');
Route::get('/best-deal', [PageController::class, 'bestDeals'])->name('best.deal');
// Removed service archive and details routes
Route::get('/vlogs', [PageController::class, 'vlogs'])->name('vlogs');
Route::get('/pages/{slug}', [PageController::class, 'additionalPage'])->name('additionalPage.show');
Route::get('/additional-pages/{slug}', [PageController::class, 'additionalPage'])->name('additionalPages.show');


Route::get('/invoice/print/{invoice_number}', [InvoiceController::class, 'print'])->name('invoice.print');
Route::get('/search', [PageController::class, 'search'])->name('search');

Route::get('/dashboard', function () {
    return view('dashboard');
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
    Route::get('/request-service', [ServiceController::class, 'request'])->name('service.request');
    Route::post('/request-service', [ServiceController::class, 'submitRequest'])->name('service.request.submit');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Checkout
    Route::get('/checkout', [OrderController::class, 'checkoutPage'])->name('checkout');

    // Order
    Route::post('/make-order', [OrderController::class, 'makeOrder'])->name('order.make');
    Route::post('/cancel-order/{orderId}', [OrderController::class, 'cancelOrder'])->name('order.cancel');
    Route::delete('/delete-order/{orderId}', [OrderController::class, 'deleteOrder'])->name('order.delete');
    Route::get('/order-success/{orderId}', [OrderController::class, 'orderSuccess'])->name('order.success');
    Route::get('/order-details/{orderNum}', [OrderController::class, 'show'])->name('order.details');

    // Wishlist
    Route::get('/wishlists', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'index'])->name('wishlist.index');
    Route::get('/wishlist/count', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'wishlistCount'])->name('wihslist.count');
    Route::post('/add-remove-wishlist/{productId}', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'addToWishlist'])->name('wishlist.add');
    Route::delete('/remove-wishlis', [\App\Http\Controllers\Ecommerce\WishlistController::class, 'removeAllWishlist'])->name('wishlist.removeAll');

    // Service
    Route::get('/requested-service/{service_number}', [ServiceController::class, 'show'])->name('service.request.show');
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

    // Branch Report Routes
    Route::get('/branches/report-data', [\App\Http\Controllers\Erp\BranchController::class, 'getReportData'])->name('branches.report.data');
    Route::get('/branches/export-excel', [\App\Http\Controllers\Erp\BranchController::class, 'exportExcel'])->name('branches.export.excel');
    Route::get('/branches/export-pdf', [\App\Http\Controllers\Erp\BranchController::class, 'exportPdf'])->name('branches.export.pdf');

    Route::resource('branches', \App\Http\Controllers\Erp\BranchController::class);
    Route::resource('warehouses', \App\Http\Controllers\Erp\WarehouseController::class);
    Route::resource('materials', \App\Http\Controllers\Erp\MaterialController::class);
    Route::resource('banners', \App\Http\Controllers\Erp\BannerController::class);
    
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
    Route::get('/employees/fetch', [\App\Http\Controllers\Erp\EmployeeController::class, 'fetchEmployees']);
    Route::get('/branches/{branch}/non-branch-employees', [\App\Http\Controllers\Erp\BranchController::class, 'getNonBranchEmployee'])->name('branches.non_branch_employees');
    Route::post('/branches/{branch}/add-employee/{employee}', [\App\Http\Controllers\Erp\BranchController::class, 'addEmployee'])->name('branches.add_employee');
    Route::post('/branches/remove-employee/{employee}', [\App\Http\Controllers\Erp\BranchController::class, 'removeEmployeeFromBranch'])->name('branches.remove_employee');
    Route::post('/branches/{branch}/warehouses', [\App\Http\Controllers\Erp\WarehouseController::class, 'storeWarehousePerBranch'])->name('branches.warehouses.store');
    Route::patch('/warehouses/{warehouse}', [\App\Http\Controllers\Erp\WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{warehouse}', [\App\Http\Controllers\Erp\WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    Route::get('warehouse/show/{warehouse}', [\App\Http\Controllers\Erp\WarehouseController::class, 'show'])->name('warehouses.show');


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
    Route::get('/products/search-with-filters/{branchId}', [\App\Http\Controllers\Erp\ProductController::class, 'searchProductWithFilters'])->name('product.searchWithFilters');
    Route::delete('/products/gallery/{id}', [\App\Http\Controllers\Erp\ProductController::class, 'deleteGalleryImage'])->name('product.gallery.delete');
    Route::post('/products/gallery', [\App\Http\Controllers\Erp\ProductController::class, 'addGalleryImage'])->name('product.gallery.add');
    Route::get('/products', [\App\Http\Controllers\Erp\ProductController::class, 'index'])->name('product.list');
    Route::get('/products/new', [\App\Http\Controllers\Erp\ProductController::class, 'create'])->name('product.create');
    Route::post('/products', [\App\Http\Controllers\Erp\ProductController::class, 'store'])->name('product.store');
    Route::get('/products/{product}', [\App\Http\Controllers\Erp\ProductController::class, 'show'])->name('product.show');
    Route::get('/products/{product}/edit', [\App\Http\Controllers\Erp\ProductController::class, 'edit'])->name('product.edit');
    Route::patch('/products/{product}', [\App\Http\Controllers\Erp\ProductController::class, 'update'])->name('product.update');
    Route::delete('/products/{product}', [\App\Http\Controllers\Erp\ProductController::class, 'destroy'])->name('product.delete');
    Route::get('/products/{id}/price', [\App\Http\Controllers\Erp\ProductController::class, 'getPrice']);

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

    // Stock
    Route::get('/product-stock', [\App\Http\Controllers\Erp\StockController::class, 'stocklist'])->name('productstock.list');
    Route::post('/stock/add-to-branches', [\App\Http\Controllers\Erp\StockController::class, 'addStockToBranches'])->name('stock.addToBranches');
    Route::post('/stock/add-to-warehouses', [App\Http\Controllers\Erp\StockController::class, 'addStockToWarehouses'])->name('stock.addToWarehouses');
    Route::post('/stock/adjust', [\App\Http\Controllers\Erp\StockController::class, 'adjustStock'])->name('stock.adjust');

    // Transfers
    Route::get('/stock-transfer', [\App\Http\Controllers\Erp\StockTransferController::class, 'index'])->name('stocktransfer.list');
    Route::get('/stock-transfer/{id}', [\App\Http\Controllers\Erp\StockTransferController::class, 'show'])->name('stocktransfer.show');
    Route::post('/stock-transfer', [\App\Http\Controllers\Erp\StockTransferController::class, 'store'])->name('stocktransfer.store');
    Route::patch('/stock-transfer/{id}/status', [\App\Http\Controllers\Erp\StockTransferController::class, 'updateStatus'])->name('stocktransfer.status');

    // Supplier
    Route::get('/supplier', [\App\Http\Controllers\Erp\SupplierController::class, 'index'])->name('supplier.list');
    Route::get('/supplier/{id}', [\App\Http\Controllers\Erp\SupplierController::class, 'show'])->name('supplier.show');
    Route::post('/suppliers', [\App\Http\Controllers\Erp\SupplierController::class, 'store'])->name('supplier.store');
    Route::patch('/suppliers/{id}', [\App\Http\Controllers\Erp\SupplierController::class, 'update'])->name('supplier.update');
    Route::delete('/suppliers/{id}', [\App\Http\Controllers\Erp\SupplierController::class, 'delete'])->name('supplier.delete');
    Route::get('/suppliers/search', [\App\Http\Controllers\Erp\SupplierController::class, 'supplierSearch'])->name('supplier.search');

    // Purchase
    Route::get('/purchases/search', [\App\Http\Controllers\Erp\PurchaseController::class, 'searchPurchase'])->name('purchase.search');
    Route::get('/purchase-products/search/{id}', [\App\Http\Controllers\Erp\PurchaseController::class, 'getItemByPurchase'])->name('purchaseitem.search');
    Route::get('/purchases', [\App\Http\Controllers\Erp\PurchaseController::class, 'index'])->name('purchase.list');
    Route::get('/purchases/create', [\App\Http\Controllers\Erp\PurchaseController::class, 'create'])->name('purchase.create');
    Route::post('/purchases/store', [\App\Http\Controllers\Erp\PurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/purchases/{id}', [\App\Http\Controllers\Erp\PurchaseController::class, 'show'])->name('purchase.show');
    Route::get('/purchases/{id}/edit', [\App\Http\Controllers\Erp\PurchaseController::class, 'edit'])->name('purchase.edit');
    Route::post('/purchases/{id}', [\App\Http\Controllers\Erp\PurchaseController::class, 'update'])->name('purchase.update');
    Route::post('/purchases/{id}/status', [\App\Http\Controllers\Erp\PurchaseController::class, 'updateStatus'])->name('purchase.updateStatus');
    Route::post('/purchases/{id}/delete', [\App\Http\Controllers\Erp\PurchaseController::class, 'delete'])->name('purchase.delete');

    // Bill
    Route::get('/bills', [\App\Http\Controllers\Erp\BillController::class, 'index'])->name('bill.list');
    Route::get('/bills/create', [\App\Http\Controllers\Erp\BillController::class, 'create'])->name('bill.create');
    Route::post('/bills/store', [\App\Http\Controllers\Erp\BillController::class, 'store'])->name('bill.store');
    Route::get('/bills/{id}', [\App\Http\Controllers\Erp\BillController::class, 'show'])->name('bill.show');
    Route::get('/bills/{id}/edit', [\App\Http\Controllers\Erp\BillController::class, 'edit'])->name('bill.edit');
    Route::post('/bills/{id}/update', [\App\Http\Controllers\Erp\BillController::class, 'update'])->name('bill.update');
    Route::post('/bills/{id}/delete', [\App\Http\Controllers\Erp\BillController::class, 'delete'])->name('bill.delete');
    Route::post('/bills/{id}/add-payment', [\App\Http\Controllers\Erp\BillController::class, 'addPayment'])->name('bill.addPayment');

    // Purchase Return
    Route::get('/purchase-return/searchbytype/{productId}/{fromId}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'getStockByType'])->name('purchaseReturn.getStockByType');
    Route::get('/purchase-return', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'index'])->name('purchaseReturn.list');
    Route::get('/purchase-return/create', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'create'])->name('purchaseReturn.create');
    Route::get('/purchase-return/{id}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'show'])->name('purchaseReturn.show');
    Route::get('/purchase-return/{id}/edit', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'edit'])->name('purchaseReturn.edit');
    Route::put('/purchase-return/{id}', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'update'])->name('purchaseReturn.update');
    Route::post('/purchase-return/store', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'store'])->name('purchaseReturn.store');
    Route::post('/purchase-return/{id}/update-status', [\App\Http\Controllers\Erp\PurchaseReturnController::class, 'updateReturnStatus'])->name('purchaseReturn.updateStatus');

    // Sale Return
    Route::get('/sale-return', [\App\Http\Controllers\Erp\SaleReturnController::class, 'index'])->name('saleReturn.list');
    Route::get('/sale-return/create', [\App\Http\Controllers\Erp\SaleReturnController::class, 'create'])->name('saleReturn.create');
    Route::post('/sale-return/store', [\App\Http\Controllers\Erp\SaleReturnController::class, 'store'])->name('saleReturn.store');
    Route::get('/sale-return/{id}', [\App\Http\Controllers\Erp\SaleReturnController::class, 'show'])->name('saleReturn.show');
    Route::get('/sale-return/{id}/edit', [\App\Http\Controllers\Erp\SaleReturnController::class, 'edit'])->name('saleReturn.edit');
    Route::put('/sale-return/{id}', [\App\Http\Controllers\Erp\SaleReturnController::class, 'update'])->name('saleReturn.update');
    Route::delete('/sale-return/{id}', [\App\Http\Controllers\Erp\SaleReturnController::class, 'destroy'])->name('saleReturn.delete');
    Route::post('/sale-return/{id}/update-status', [\App\Http\Controllers\Erp\SaleReturnController::class, 'updateReturnStatus'])->name('saleReturn.updateStatus');

    // Customer
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

    // POS Report Routes (must come before /pos/{id} to avoid route conflicts)
    Route::get('/pos/report-data', [\App\Http\Controllers\Erp\PosController::class, 'getReportData'])->name('pos.report.data');
    Route::get('/pos/export-excel', [\App\Http\Controllers\Erp\PosController::class, 'exportExcel'])->name('pos.export.excel');
    Route::get('/pos/export-pdf', [\App\Http\Controllers\Erp\PosController::class, 'exportPdf'])->name('pos.export.pdf');

    Route::get('/pos/{id}', [\App\Http\Controllers\Erp\PosController::class, 'show'])->name('pos.show');
    Route::post('/pos/assign-tech/{saleId}/{techId}', [\App\Http\Controllers\Erp\PosController::class, 'assignTechnician'])->name('pos.assign.tech');
    Route::post('/pos/update-note/{saleId}', [\App\Http\Controllers\Erp\PosController::class, 'updateNote'])->name('pos.update.note');
    Route::post('/pos/add-payment/{saleId}', [\App\Http\Controllers\Erp\PosController::class, 'addPayment'])->name('pos.add.payment');
    Route::post('/erp/pos/update-status/{saleId}', [\App\Http\Controllers\Erp\PosController::class, 'updateStatus'])->name('pos.update.status');
    Route::post('/erp/pos/add-address/{invoiceId}', [\App\Http\Controllers\Erp\PosController::class, 'addAddress'])->name('pos.add.address');

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
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoice.show');
    Route::post('/invoices/add-payment/{id}', [InvoiceController::class, 'addPayment'])->name('invoice.addPayment');
    Route::get('/erp/invoices/{id}/edit', [InvoiceController::class, 'edit'])->name('invoice.edit');
    Route::patch('/erp/invoices/{id}', [InvoiceController::class, 'update'])->name('invoice.update');

    // Order
    Route::get('/order-list', [\App\Http\Controllers\Erp\OrderController::class, 'index'])->name('order.list');
    Route::get('/order/search', [\App\Http\Controllers\Erp\OrderController::class, 'orderSearch'])->name('order.search');
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
    Route::get('/order-return/create', [\App\Http\Controllers\Erp\OrderReturnController::class, 'create'])->name('orderReturn.create');
    Route::post('/order-return/store', [\App\Http\Controllers\Erp\OrderReturnController::class, 'store'])->name('orderReturn.store');
    Route::get('/order-return/{id}', [\App\Http\Controllers\Erp\OrderReturnController::class, 'show'])->name('orderReturn.show');
    Route::get('/order-return/{id}/edit', [\App\Http\Controllers\Erp\OrderReturnController::class, 'edit'])->name('orderReturn.edit');
    Route::put('/order-return/{id}', [\App\Http\Controllers\Erp\OrderReturnController::class, 'update'])->name('orderReturn.update');
    Route::delete('/order-return/{id}', [\App\Http\Controllers\Erp\OrderReturnController::class, 'destroy'])->name('orderReturn.delete');
    Route::post('/order-return/{id}/update-status', [\App\Http\Controllers\Erp\OrderReturnController::class, 'updateReturnStatus'])->name('orderReturn.updateStatus');

    // Customer Services
    Route::get('/customer-services/search', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'search'])->name('customerService.search');
    Route::get('/customer-services', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'index'])->name('customerService.list');
    Route::get('/customer-services/create', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'create'])->name('customerService.create');
    Route::post('/customer-services/store', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'store'])->name('service.store');
    Route::get('/customer-services/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'show'])->name('customerService.show');
    Route::post('/customer-services/update-technician/{id}/{employee_id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateTechnician'])->name('customerService.updateTechnician');
    Route::post('/customer-services/remove-technician/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'deleteTechnician'])->name('customerService.deleteTechnician');
    Route::post('/customer-services/product-stock-add/{serviceId}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addStockToServiceItem'])->name('customerService.addStockToServiceItem');
    Route::post('/customer-services/transfer-stock-to-employee/{serviceId}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'transferStockToEmployee'])->name('customerService.transferStockToEmployee');
    Route::post('/customer-services/add-payment/{serviceId}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addPayment'])->name('customerService.add.payment');
    Route::post('/customer-services/update-note/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateNote'])->name('customerService.updateNote');
    Route::post('/customer-services/add-address/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addAddress'])->name('customerService.addAddress');
    Route::post('/customer-services/update-status/{id}', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateStatus'])->name('customerService.updateStatus');
    Route::post('/customer-services/add-extra-part', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'addExtraPart'])->name('customerService.addExtraPart');
    Route::post('/customer-services/delete-extra-part', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'deleteExtraPart'])->name('customerService.deleteExtraPart');
    Route::post('/customer-services/update-service-fees', [\App\Http\Controllers\Erp\CustomerServiceController::class, 'updateServiceFees'])->name('customerService.updateServiceFees');

    // Double Entry
    Route::get('/account-type', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'accountType'])->name('account-type.list');
    Route::post('/account-type', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'accountTypeStore'])->name('account-type.store');
    Route::put('/account-type/{id}', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'accountTypeUpdate'])->name('account-type.update');
    Route::delete('/account-type/{id}', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'accountTypeDelete'])->name('account-type.delete');
    Route::get('/account-type/{typeId}/sub-types', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'getSubTypesByType'])->name('account-type.sub-types');
    Route::get('/chart-of-account', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'list'])->name('chart-of-account.list');
    Route::post('/chart-of-account', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'store'])->name('chart-of-account.store');
    Route::put('/chart-of-account/{id}', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'update'])->name('chart-of-account.update');
    Route::delete('/chart-of-account/{id}', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'destroy'])->name('chart-of-account.delete');
    Route::post('/chart-of-account/parent', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'storeParent'])->name('chart-of-account.parent.store');
    Route::put('/chart-of-account/parent/{id}', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'updateParent'])->name('chart-of-account.parent.update');
    Route::delete('/chart-of-account/parent/{id}', [\App\Http\Controllers\Erp\ChartOfAccountController::class, 'destroyParent'])->name('chart-of-account.parent.delete');

    // Financial Accounts
    Route::get('/financial-accounts', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'list'])->name('financial-accounts.list');
    Route::post('/financial-accounts', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'store'])->name('financial-accounts.store');
    Route::put('/financial-accounts/{id}', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'update'])->name('financial-accounts.update');
    Route::delete('/financial-accounts/{id}', [\App\Http\Controllers\Erp\FinancialAccountController::class, 'destroy'])->name('financial-accounts.delete');

    // Journal
    Route::get('/journal', [\App\Http\Controllers\Erp\JournalController::class, 'list'])->name('journal.list');
    Route::get('/journal/{id}', [\App\Http\Controllers\Erp\JournalController::class, 'show'])->name('journal.show');
    Route::get('/journal/{id}/entries', [\App\Http\Controllers\Erp\JournalController::class, 'getEntries'])->name('journal.entries');
    Route::post('/journal', [\App\Http\Controllers\Erp\JournalController::class, 'store'])->name('journal.store');
    Route::put('/journal/{id}', [\App\Http\Controllers\Erp\JournalController::class, 'update'])->name('journal.update');
    Route::delete('/journal/{id}', [\App\Http\Controllers\Erp\JournalController::class, 'destroy'])->name('journal.delete');

    // Journal Entry Management
    Route::get('/journal-entry/{id}', [\App\Http\Controllers\Erp\JournalController::class, 'getEntry'])->name('journal.entry.show');
    Route::post('/journal/{journalId}/entry', [\App\Http\Controllers\Erp\JournalController::class, 'storeEntry'])->name('journal.entry.store');
    Route::put('/journal-entry/{id}', [\App\Http\Controllers\Erp\JournalController::class, 'updateEntry'])->name('journal.entry.update');
    Route::delete('/journal-entry/{id}', [\App\Http\Controllers\Erp\JournalController::class, 'destroyEntry'])->name('journal.entry.delete');

    // Transfer
    Route::get('/transfer', [\App\Http\Controllers\Erp\TransferController::class, 'list'])->name('transfer.list');
    Route::post('/transfer', [\App\Http\Controllers\Erp\TransferController::class, 'store'])->name('transfer.store');
    Route::post('/transfer/with-journal', [\App\Http\Controllers\Erp\TransferController::class, 'storeWithJournal'])->name('transfer.storeWithJournal');
    Route::get('/transfer/existing-journals', [\App\Http\Controllers\Erp\TransferController::class, 'getExistingJournals'])->name('transfer.existingJournals');

    // Ledger
    Route::get('/ledger', [\App\Http\Controllers\Erp\LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/{id}', [\App\Http\Controllers\Erp\LedgerController::class, 'show'])->name('ledger.show');
    Route::get('/ledger/account/{accountId}', [\App\Http\Controllers\Erp\LedgerController::class, 'accountLedger'])->name('ledger.account');

    // Balance Sheet
    Route::get('/balance-sheet', [\App\Http\Controllers\Erp\BalanceSheetController::class, 'index'])->name('balanceSheet.index');

    // Profit & Loss
    Route::get('/profit-loss', [\App\Http\Controllers\Erp\ProfitLossController::class, 'index'])->name('profitLoss.index');

    // Profit & Loss
    Route::get('/profit-and-loss', [\App\Http\Controllers\Erp\ProfitLossController::class, 'index'])->name('profitAndLoss.index');

    // User Role
    Route::get('/user-role', [\App\Http\Controllers\Erp\UserRoleController::class, 'index'])->name('userRole.index');
    Route::post('/user-role', [\App\Http\Controllers\Erp\UserRoleController::class, 'store'])->name('userRole.store');
    Route::put('/user-role/{id}', [\App\Http\Controllers\Erp\UserRoleController::class, 'update'])->name('userRole.update');

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
});

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

require __DIR__ . '/auth.php';