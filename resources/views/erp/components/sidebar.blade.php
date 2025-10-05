<div class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center">
            <a href="{{ route('erp.dashboard') }}">
                <img src="{{ $general_settings && $general_settings->site_logo ? asset($general_settings->site_logo) : asset('static/default-logo.webp') }}" alt="" class="img-fluid">
            </a>
        </div>
    </div>
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-item">
            <a href="{{ route('erp.dashboard') }}" class="nav-link {{ request()->is('erp/dashboard*') ? ' active' : '' }}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-home nav-icon"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </div>
        @can('manage global branches')
        <div class="nav-item">
            <a href="{{ route('branches.index') }}" class="nav-link {{ request()->is('erp/branches*') ? ' active' : '' }}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-code-branch nav-icon"></i>
                    <span>Branches</span>
                </div>
            </a>
        </div>
        @endcan
        <div class="nav-item">
            <a href="#" class="nav-link {{ request()->is('erp/employees*') ? ' active' : '' }}" data-bs-toggle="collapse" data-bs-target="#hrmSubmenu" aria-expanded="{{ request()->is('erp/employees*') ? 'true' : 'false' }}" aria-controls="hrmSubmenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-users nav-icon"></i>
                    <span>HRM System</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ request()->is('erp/employees*') ? ' show' : '' }}" id="hrmSubmenu">
                <ul class="nav flex-column ms-4">
                    <li class="nav-item">
                        <a href="{{ route('employees.index') }}" class="nav-link {{ request()->is('erp/employees*') ? ' active' : '' }}">Employee Setup</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">Payroll Setup</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="#accountingSubmenu" class="nav-link {{ (request()->is('erp/supplier*') || request()->is('erp/bills*') ||  request()->is('erp/customers*') || request()->is('erp/invoices*') || request()->is('erp/invoice-templates*') || request()->is('erp/account-type*') || request()->is('erp/chart-of-account*') || request()->is('erp/financial-accounts*') || request()->is('erp/journal*') || request()->is('erp/transfer*') || request()->is('erp/ledger*') || request()->is('erp/balance-sheet*') || request()->is('erp/profit-and-loss*')) ? ' active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ (request()->is('erp/supplier*') || request()->is('erp/bills*') ||  request()->is('erp/customers*') || request()->is('erp/invoices*') || request()->is('erp/invoice-templates*') || request()->is('erp/account-type*') || request()->is('erp/chart-of-account*') || request()->is('erp/financial-accounts*') || request()->is('erp/journal*') || request()->is('erp/transfer*') || request()->is('erp/ledger*') || request()->is('erp/balance-sheet*') || request()->is('erp/profit-and-loss*')) ? 'true' : 'false' }}" aria-controls="accountingSubmenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calculator nav-icon"></i>
                    <span>Accounting System</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ (request()->is('erp/supplier*') || request()->is('erp/bills*') ||  request()->is('erp/customers*') || request()->is('erp/invoices*') || request()->is('erp/invoice-templates*') || request()->is('erp/account-type*') || request()->is('erp/chart-of-account*') || request()->is('erp/financial-accounts*') || request()->is('erp/journal*') || request()->is('erp/transfer*') || request()->is('erp/ledger*') || request()->is('erp/balance-sheet*') || request()->is('erp/profit-and-loss*')) ? ' show' : '' }}" id="accountingSubmenu">
                <ul class="nav flex-column ms-4">
                    <li class="nav-item">
                        <a href="#salesSubmenu" class="nav-link {{ (request()->is('erp/customers*') || request()->is('erp/invoices*') || request()->is('erp/invoice-templates*')) ? ' active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ (request()->is('erp/customers*') || request()->is('erp/invoices*') || request()->is('erp/invoice-templates*')) ? 'true' : 'false' }}" aria-controls="salesSubmenu">
                            <span>Sales</span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse{{ (request()->is('erp/customers*') || request()->is('erp/invoices*') || request()->is('erp/invoice-templates*')) ? ' show' : '' }}" id="salesSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a href="{{ route('customers.list') }}" class="nav-link {{ request()->is('erp/customers*') ? ' active' : '' }}">Customer</a></li>
                                <li class="nav-item"><a href="{{ route('invoice.list') }}" class="nav-link {{ request()->is('erp/invoices*') ? ' active' : '' }}">Invoice</a></li>
                                <li class="nav-item"><a href="{{ route('invoice.template.list') }}" class="nav-link {{ request()->is('erp/invoice-templates*') ? ' active' : '' }}">Invoice Template</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="#purchasesSubmenu" class="nav-link {{ (request()->is('erp/supplier*') || request()->is('erp/bills*')) ? ' active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ (request()->is('erp/supplier*') || request()->is('erp/bills*')) ? 'true' : 'false' }}" aria-controls="purchasesSubmenu">
                            <span>Purchases</span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse{{ (request()->is('erp/supplier*') || request()->is('erp/bills*')) ? ' show' : '' }}" id="purchasesSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a href="{{ route('supplier.list') }}" class="nav-link {{ request()->is('erp/supplier*') ? ' active' : '' }}">Suppliers</a></li>
                                <li class="nav-item"><a href="{{ route('bill.list') }}" class="nav-link {{ request()->is('erp/bills*') ? ' active' : '' }}">Bill</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="#financialAccountsSubmenu" class="nav-link {{ (request()->is('erp/financial-accounts*') || request()->is('erp/transfer*')) ? ' active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ (request()->is('erp/financial-accounts*') || request()->is('erp/transfer*')) ? 'true' : 'false' }}" aria-controls="financialAccountsSubmenu">
                            <span>Financial Accounts</span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse{{ (request()->is('erp/financial-accounts*') || request()->is('erp/transfer*')) ? ' show' : '' }}" id="financialAccountsSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a href="{{ route('financial-accounts.list') }}" class="nav-link {{ request()->is('erp/financial-accounts*') ? ' active' : '' }}">Accounts</a></li>
                                <li class="nav-item"><a href="{{ route('transfer.list') }}" class="nav-link {{ request()->is('erp/transfer*') ? ' active' : '' }}">Transfer</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="#doubleEntrySubmenu" class="nav-link {{ (request()->is('erp/account-type*') || request()->is('erp/chart-of-account*') || request()->is('erp/journal*') || request()->is('erp/ledger*') || request()->is('erp/balance-sheet*') || request()->is('erp/profit-and-loss*')) ? ' active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ (request()->is('erp/account-type*') || request()->is('erp/chart-of-account*') || request()->is('erp/journal*') || request()->is('erp/ledger*') || request()->is('erp/balance-sheet*') || request()->is('erp/profit-and-loss*')) ? 'true' : 'false' }}" aria-controls="doubleEntrySubmenu">
                            <span>Double Entry</span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse{{ (request()->is('erp/account-type*') || request()->is('erp/chart-of-account*') || request()->is('erp/journal*') || request()->is('erp/ledger*') || request()->is('erp/balance-sheet*') || request()->is('erp/profit-and-loss*')) ? ' show' : '' }}" id="doubleEntrySubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item"><a href="{{ route('account-type.list') }}" class="nav-link {{ request()->is('erp/account-type*') ? ' active' : '' }}">Account Type</a></li>
                                <li class="nav-item"><a href="{{ route('chart-of-account.list') }}" class="nav-link {{ request()->is('erp/chart-of-account*') ? ' active' : '' }}">Chart of Account</a></li>
                                <li class="nav-item"><a href="{{ route('journal.list') }}" class="nav-link {{ request()->is('erp/journal*') ? ' active' : '' }}">Journal Account</a></li>
                                <li class="nav-item"><a href="{{ route('ledger.index') }}" class="nav-link {{ request()->is('erp/ledger*') ? ' active' : '' }}">Ledger Summary</a></li>
                                <li class="nav-item"><a href="{{ route('balanceSheet.index') }}" class="nav-link {{ request()->is('erp/balance-sheet*') ? ' active' : '' }}">Balance Sheet</a></li>
                                <li class="nav-item"><a href="{{ route('profitAndLoss.index') }}" class="nav-link {{ request()->is('erp/profit-and-loss*') ? ' active' : '' }}">Profit & Loss</a></li>
                                <li class="nav-item"><a href="#" class="nav-link">Trial Balance</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="#" class="nav-link">
                <div class="d-flex align-items-center">
                    <i class="fas fa-phone nav-icon"></i>
                    <span>CRM System</span>
                </div>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div class="nav-item">
            <a href="#" class="nav-link {{ (request()->is('erp/user-role*')) ? ' active' : '' }}" data-bs-toggle="collapse" data-bs-target="#userManagementSubMenu" aria-expanded="{{ (request()->is('erp/user-role*')) ? 'true' : 'false' }}" aria-controls="userManagementSubMenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-check nav-icon"></i>
                    <span>User Management</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ (request()->is('erp/user-role*')) ? ' show' : '' }}" id="userManagementSubMenu">
                <ul class="nav flex-column ms-4">
                    <li class="nav-item">
                        <a href="{{ route('userRole.index') }}" class="nav-link {{ request()->is('erp/user-role*') ? ' active' : '' }}">User Role</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="#" class="nav-link {{ (request()->is('erp/categories*') || request()->is('erp/products*') || request()->is('erp/product-stock*') || request()->is('erp/attributes*') || request()->is('erp/subcategories*')) ? ' active' : '' }}" data-bs-toggle="collapse" data-bs-target="#productsSubMenu" aria-expanded="{{ (request()->is('erp/categories*') || request()->is('erp/products*') || request()->is('erp/product-stock*') || request()->is('erp/attributes*') || request()->is('erp/subcategories*')) ? 'true' : 'false' }}" aria-controls="productsSubMenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-box nav-icon"></i>
                    <span>Products System</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ (request()->is('erp/categories*') || request()->is('erp/products*') || request()->is('erp/product-stock*') || request()->is('erp/attributes*') || request()->is('erp/subcategories*')) ? ' show' : '' }}" id="productsSubMenu">
                <ul class="nav flex-column ms-4">
                    <li class="nav-item">
                        <a href="{{ route('category.list') }}" class="nav-link {{ request()->is('erp/categories*') ? ' active' : '' }}">Category</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('subcategory.list') }}" class="nav-link {{ request()->is('erp/subcategories*') ? ' active' : '' }}">Sub Categories</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('product.list') }}" class="nav-link {{ request()->is('erp/products*') ? ' active' : '' }}">Products</a>
                    </li>
                    <li class="nav-item">
                        <a href="" class="nav-link">Raw Material</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('attribute.list') }}" class="nav-link {{ request()->is('erp/attributes*') ? ' active' : '' }}">Attributes</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('productstock.list') }}" class="nav-link {{ request()->is('erp/product-stock*') ? ' active' : '' }}">Product Stock</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('reviews.index') }}" class="nav-link {{ request()->is('erp/reviews*') ? ' active' : '' }}">Reviews</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="#posSubmenu" class="nav-link {{ (request()->is('erp/stock-transfer*') || request()->is('erp/purchases*') || request()->is('erp/purchase-return*') || request()->is('erp/pos*') || request()->is('erp/sale-return*')) ? ' active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ (request()->is('erp/stock-transfer*') || request()->is('erp/purchases*') || request()->is('erp/purchase-return*') || request()->is('erp/pos*') || request()->is('erp/sale-return*')) ? 'true' : 'false' }}" aria-controls="posSubmenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-cash-register nav-icon"></i>
                    <span>POS System</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ (request()->is('erp/stock-transfer*') || request()->is('erp/purchases*') || request()->is('erp/purchase-return*') || request()->is('erp/pos*') || request()->is('erp/sale-return*')) ? ' show' : '' }}" id="posSubmenu">
                <ul class="nav flex-column ms-4">
                    @can('make sale')
                    <li class="nav-item">
                        <a href="{{ route('pos.add') }}" class="nav-link {{ request()->is('erp/pos/create') ? ' active' : '' }}">Add POS</a>
                    </li>
                    @endcan
                    @can('view sales')
                    <li class="nav-item">
                        <a href="{{ route('pos.list') }}" class="nav-link {{ request()->is('erp/pos') ? ' active' : '' }}">POS</a>
                    </li>
                    @endcan
                    <li class="nav-item">
                        <a href="{{ route('saleReturn.list') }}" class="nav-link {{ request()->is('erp/sale-return*') ? ' active' : '' }}">Sale Return</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('purchaseReturn.list')}}" class="nav-link {{ request()->is('erp/purchase-return*') ? ' active' : '' }}">Purchase Return</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('stocktransfer.list') }}" class="nav-link {{ request()->is('erp/stock-transfer*') ? ' active' : '' }}">Transfer</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('purchase.list') }}" class="nav-link {{ request()->is('erp/purchases*') ? ' active' : '' }}">Purchase</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="#" class="nav-link {{ request()->is('erp/customer-services*') ? ' active' : '' }}" data-bs-toggle="collapse" data-bs-target="#serviceSubMenu" aria-expanded="{{ request()->is('erp/customer-services*') ? 'true' : 'false' }}" aria-controls="productsSubMenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-headset nav-icon"></i>
                    <span>Customer Services</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ request()->is('erp/customer-services*') ? ' show' : '' }}" id="serviceSubMenu">
                <ul class="nav flex-column ms-4">
                    <li class="nav-item">
                        <a href="{{ route('customerService.list') }}" class="nav-link {{ request()->is('erp/customer-services*') ? ' active' : '' }}">Service</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="#" class="nav-link {{ (request()->is('erp/order-list*') || request()->is('erp/order-return*')) ? ' active' : '' }}" data-bs-toggle="collapse" data-bs-target="#ecommerceSubMenu" aria-expanded="{{ (request()->is('erp/order-list*') || request()->is('erp/order-return*')) ? 'true' : 'false' }}" aria-controls="productsSubMenu">
                <div class="d-flex align-items-center">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                    <span>Ecommerce</span>
                </div>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse{{ (request()->is('erp/order-list*') || request()->is('erp/order-return*')) ? ' show' : '' }}" id="ecommerceSubMenu">
                <ul class="nav flex-column ms-4">
                    <li class="nav-item">
                        <a href="{{ route('order.list') }}" class="nav-link {{ request()->is('erp/order-list*') ? ' active' : '' }}">Order</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('orderReturn.list') }}" class="nav-link {{ request()->is('erp/order-return*') ? ' active' : '' }}">Order Return</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="nav-item">
            <a href="{{ route('vlogging.index') }}" class="nav-link {{ request()->is('erp/vlogging*') ? ' active' : '' }}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-video nav-icon"></i>
                    <span>Vlogs</span>
                </div>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('additionalPages.index') }}" class="nav-link {{ request()->is('erp/additional-pages*') ? ' active' : '' }}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-file-alt nav-icon"></i>
                    <span>Additional Pages</span>
                </div>
            </a>
        </div>
        @can('view banners')
        <div class="nav-item">
            <a href="{{ route('banners.index') }}" class="nav-link {{ request()->is('erp/banners*') ? ' active' : '' }}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-image nav-icon"></i>
                    <span>Banner Management</span>
                </div>
            </a>
        </div>
        @endcan
        @can('view settings')
        <div class="nav-item">
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->is('erp/settings*') ? ' active' : '' }}">
                <div class="d-flex align-items-center">
                    <i class="fas fa-cog nav-icon"></i>
                    <span>Settings</span>
                </div>
            </a>
        </div>
        @endcan
    </nav>
</div> 