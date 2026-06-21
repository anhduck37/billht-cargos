<!-- Header / General Info -->
<div class="col-sm-3">
    {!! Form::label('order_code', __('order.invoice_code')) !!}
    <p>{{ $order->order_code }}</p>
    @if(isset($order->code_aliases) && $order->code_aliases->count())
        <small class="text-muted">
            Mã cũ:
            {{ $order->code_aliases->pluck('old_code')->unique()->implode(', ') }}
        </small>
    @endif
</div>
<div class="col-sm-3">
    {!! Form::label('invoice_code', __('order.tracking_code')) !!}
    <p>{{ $order->invoice_code }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('order_status', __('order.order_status')) !!}
    <p>{{ $order->order_status_name }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('delivery_status', __('order.delivery_status')) !!}
    <p>{{ $order->order_delivery_name }}</p>
</div>

<hr class="col-sm-12" />

<!-- Sender Info -->
<div class="col-sm-6">
    <h4>Thông tin người gửi</h4>
    <p><strong>{{ __('order.name') }}:</strong> {{ $order->sender->sender_name ?? 'N/A' }}</p>
    <p><strong>{{ __('order.phone') }}:</strong> {{ $order->sender->sender_phone ?? 'N/A' }}</p>
    <p><strong>{{ __('order.email') }}:</strong> {{ $order->sender->sender_email ?? 'N/A' }}</p>
    <p><strong>Địa chỉ:</strong> {{ isset($order->sender) ? $order->sender->full_address : '' }}</p>
</div>

<!-- Receiver Info -->
<div class="col-sm-6">
    <h4>Thông tin người nhận</h4>
    <p><strong>{{ __('order.name') }}:</strong> {{ $order->receiver->receiver_name ?? 'N/A' }}</p>
    <p><strong>{{ __('order.phone') }}:</strong> {{ $order->receiver->receiver_phone ?? 'N/A' }}</p>
    <p><strong>{{ __('order.email') }}:</strong> {{ $order->receiver->receiver_email ?? 'N/A' }}</p>
    <p><strong>Địa chỉ:</strong> {{ isset($order->receiver) ? $order->receiver->full_address : '' }}</p>
</div>

<hr class="col-sm-12" />

<!-- Order Details -->
<div class="col-sm-3">
    {!! Form::label('weight', 'Trọng lượng (g)') !!}
    <p>{{ $order->weight }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('dimension', 'Kích thước (DxRxC)') !!}
    <p>{{ $order->long }} x {{ $order->width }} x {{ $order->height }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('total', __('order.total')) !!}
    <p>{{ number_format($order->total) }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('collection', 'Thu hộ (COD)') !!}
    <p>{{ number_format($order->collection) }}</p>
</div>

<div class="col-sm-3">
    {!! Form::label('payment_method', 'Thanh toán') !!}
    <p>{{ $order->payment_method_name }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('quantity', 'Số lượng') !!}
    <p>{{ $order->quantity }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('created_at', __('order.created_at')) !!}
    <p>{{ $order->created_at->format('H:i d/m/Y') }}</p>
</div>
<div class="col-sm-12">
    {!! Form::label('note', __('order.note')) !!}
    <p>{{ $order->note }}</p>
</div>
