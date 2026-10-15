<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Due Management</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .muted { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Due Management Report</h1>
    <div class="muted">Generated {{ $generatedAt->format('d M Y H:i') }} · Total outstanding ৳ {{ number_format($totalOutstanding, 2) }}</div>
    <table>
        <thead>
            <tr>
                <th>Shop</th><th>Salesman</th><th class="right">Outstanding</th><th class="right">Last payment</th>
                <th>Last date</th><th class="right">Days</th><th class="right">Sales</th><th class="right">Collected</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['shop']->name }} ({{ $row['shop']->code }})</td>
                    <td>{{ $row['salesman']?->name ?: '—' }}</td>
                    <td class="right">{{ number_format($row['outstanding'], 2) }}</td>
                    <td class="right">{{ number_format($row['last_payment_amount'], 2) }}</td>
                    <td>{{ $row['last_payment_date']?->format('Y-m-d') ?: '—' }}</td>
                    <td class="right">{{ $row['days_outstanding'] }}</td>
                    <td class="right">{{ number_format($row['total_sales'], 2) }}</td>
                    <td class="right">{{ number_format($row['total_collections'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
