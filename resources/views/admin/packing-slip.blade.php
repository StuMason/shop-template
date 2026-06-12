<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Packing slip — {{ $order->number }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 2.5rem; color: #111; }
        h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
        .muted { color: #666; font-size: .85rem; }
        .grid { display: flex; gap: 3rem; margin: 1.5rem 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #ddd; font-size: .9rem; }
        th { border-bottom: 2px solid #111; }
        .qty { text-align: center; width: 4rem; }
        .note { margin-top: 1.5rem; padding: .75rem; border: 1px solid #ddd; font-size: .9rem; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>

    <h1>{{ $shopName }}</h1>
    <p class="muted">Packing slip — this is not a receipt; no prices are shown.</p>

    <div class="grid">
        <div>
            <strong>Order</strong><br>
            {{ $order->number }}<br>
            <span class="muted">Placed {{ $order->placed_at->format('j M Y') }}</span>
        </div>
        <div>
            <strong>Deliver to</strong><br>
            {{ $order->shipping_address['name'] }}<br>
            {{ $order->shipping_address['line1'] }}<br>
            @if ($order->shipping_address['line2'])
                {{ $order->shipping_address['line2'] }}<br>
            @endif
            {{ $order->shipping_address['city'] }}, {{ $order->shipping_address['postcode'] }}<br>
            {{ $order->shipping_address['country'] }}
        </div>
        <div>
            <strong>Delivery</strong><br>
            {{ $order->shipping_method_name }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="qty">Qty</th>
                <th>Item</th>
                <th>SKU</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="qty">{{ $item->quantity }}</td>
                    <td>
                        {{ $item->product_name }}
                        @if ($item->variant_name !== 'Default')
                            ({{ $item->variant_name }})
                        @endif
                    </td>
                    <td>{{ $item->sku }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($order->customer_note)
        <div class="note"><strong>Customer note:</strong> {{ $order->customer_note }}</div>
    @endif
</body>
</html>
