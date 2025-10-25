@if($orders->count() > 0)
<div class="d-flex justify-content-center mt-3">
    {{ $orders->appends(['tab' => 'orders', 'status' => request('status')])->links('vendor.pagination.bootstrap-5') }}
</div>
@endif
