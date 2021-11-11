<!-- Created At Field -->
<div class="col-sm-3">
    {!! Form::label('customer_name', __('order.name')) !!}
    <p>{{ $order->customer_name }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('customer_phone', __('order.phone')) !!}
    <p>{{ $order->customer_phone }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('customer_email', __('order.email')) !!}
    <p>{{ $order->customer_email }}</p>
</div>

<div class="col-sm-3">
    {!! Form::label('order_status', __('order.order_status')) !!}
    <p>{{ $order->status_name }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('invoice_code', __('order.invoice_code')) !!}
    <p>{{ $order->invoice_code }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('delivery_status', __('order.delivery_status')) !!}
    <p>{{ $order->delivery_name }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('delivery_date', __('order.delivery_date')) !!}
    <p>{{ $order->delivery_date }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('tracking_code', __('order.tracking_code')) !!}
    <p>{{ $order->tracking_code }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('total', __('order.total')) !!}
    <p>{{ $order->total }}</p>
</div>

<div class="col-sm-3">
    {!! Form::label('total_payment', __('order.total_payment')) !!}
    <p>@if($order->is_paid_profit == \App\Models\order::ORDER_UNPAID_PROFIT)

    @else   
        {{ ($order->total * $order->percent_commission)/100 }} ( {{ $order->percent_commission }} %  )
    @endif</p>
</div>
<div class="col-sm-3">
    {!! Form::label('lang', __('order.lang')) !!}
    <p>{{ $order->lang }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('created_at', __('order.created_at')) !!}
    <p>{{ $order->created_at }}</p>
</div>
<div class="col-sm-3">
    {!! Form::label('updated_at', __('order.updated_at')) !!}
    <p>{{ $order->updated_at }}</p>
</div>
<hr/>
<div class="col-sm-12 mt-4">
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('orderItem.product') }}</th>
                <th>{{ __('orderItem.unit_price') }}</th>
                <th>{{ __('orderItem.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @if($order->orderItem->isNotEmpty())
                @foreach($order->orderItem as $orderItem)
                <tr>
                    <td>{{ $orderItem->product_code }}</td>
                    <td>{{ $orderItem->unit_price }}</td>
                    <td>{{ $orderItem->quantity }}</td>
                </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
