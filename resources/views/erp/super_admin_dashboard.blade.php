@extends('erp.master')

@section('title', 'Super Admin Dashboard')

@push('css')
    <link href="{{ asset('css/super_admin_dashboard.css') }}?v=1.0.2" rel="stylesheet">
@endpush

@section('body')
    @include('erp.components.sidebar')
    
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        
        <div class="container-fluid p-3 p-md-4">
            
            <!-- Dashboard Executive Header -->
            @include('erp.components.super_admin.header')

            <!-- SECTION 1 & SECTION 2 ROW -->
            <div class="row g-4 mb-4">
                @include('erp.components.super_admin.today_sales')
                @include('erp.components.super_admin.sales_chart')
            </div>

            <!-- SECTION 3: Branch Sales Statement (Full Width 100%) -->
            @include('erp.components.super_admin.branch_statement')

            <!-- SECTION 4: Top Selling Products (Full Width 100%) -->
            @include('erp.components.super_admin.top_products')

            <!-- SECTION 5: Gross Sales Statement -->
            @include('erp.components.super_admin.gross_statement')

            <!-- SECTION 6: Expense Statement -->
            @include('erp.components.super_admin.expense_statement')

        </div>
    </div>
@endsection

@push('scripts')
    @include('erp.components.super_admin.scripts')
@endpush
