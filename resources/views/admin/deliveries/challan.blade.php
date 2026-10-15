<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Challan {{ $delivery->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #555; margin-bottom: 12px; }
        .meta { margin-bottom: 16px; }
        .meta td { padding: 3px 12px 3px 0; vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 6px 8px; }
        table.items th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .sign { margin-top: 36px; display: flex; justify-content: space-between; }
        .sign div { width: 40%; border-top: 1px solid #333; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Delivery Challan</h1>
    <div class="muted">{{ $delivery->number }} · Generated {{ $generatedAt->format('d M Y H:i') }}</div>
    <table class="meta">
        <tr><td><strong>Shop</strong></td><td>{{ $delivery->shop?->name }}</td></tr>
        <tr><td><strong>Address</strong></td><td>{{ $delivery->delivery_address ?: $delivery->shop?->address }}</td></tr>
        <tr><td><strong>Order</strong></td><td>{{ $delivery->order?->number }}</td></tr>
        <tr><td><strong>Warehouse</strong></td><td>{{ $delivery->warehouse?->name }}</td></tr>
        <tr><td><strong>Delivery person</strong></td><td>{{ $delivery->assignee?->name ?: '—' }}</td></tr>
        <tr><td><strong>Dispatched</strong></td><td>{{ $delivery->dispatched_at?->format('d M Y H:i') ?: '—' }}</td></tr>
        <tr><td><strong>Delivered</strong></td><td>{{ $delivery->delivered_at?->format('d M Y H:i') ?: '—' }}</td></tr>
    </table>
    <table class="items">
        <thead>
            <tr><th>SKU</th><th>Product</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Line total</th></tr>
        </thead>
        <tbody>
            @foreach ($delivery->order->items as $item)
                <tr>
                    <td>{{ $item->product_sku }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top:12px"><strong>Order total:</strong> ৳ {{ number_format((float) ($delivery->order?->total ?? 0), 2) }}</p>
    <div class="sign">
        <div>Received by (shop)</div>
        <div>Delivered by</div>
    </div>
</body>
</html>
