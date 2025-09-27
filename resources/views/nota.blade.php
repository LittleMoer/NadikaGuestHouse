<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nota Booking #{{ $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color:#111; }
        .nota { max-width: 420px; margin: 0 auto; }
        h1 { font-size: 1.1rem; margin: 0 0 6px; }
        .muted { color:#555; font-size:.8rem; }
        table { width:100%; border-collapse: collapse; margin-top:10px; }
        td { padding:6px 0; font-size:.9rem; }
        .right { text-align:right; }
        .divider { border-top:1px dashed #999; margin:8px 0; }
        .total-row td { font-weight:700; border-top:1px solid #000; padding-top:8px; }
        .meta { margin-top:8px; font-size:.8rem; }
        .print-btn { margin: 12px 0; }
        @media print { .print-btn { display:none; } }
    </style>
</head>
<body>
    <div class="nota">
        <h1>Nota Booking #{{ $order->id }}</h1>
        <div class="muted">Tanggal: {{ now()->format('d/m/Y H:i') }}</div>
        <div class="muted">Pelanggan: {{ $order->pelanggan?->nama ?? '-' }} ({{ $order->pelanggan?->telepon ?? '-' }})</div>
        <div class="muted">Check-in: {{ $order->tanggal_checkin->format('d/m/Y H:i') }}</div>
        <div class="muted">Check-out: {{ $order->tanggal_checkout->format('d/m/Y H:i') }}</div>
        <div class="muted">Jumlah Tamu: {{ $order->jumlah_tamu_total ?? '-' }}</div>

        <div class="divider"></div>
        <table>
            <tr>
                <td>Total Kamar</td>
                <td class="right">Rp {{ number_format($roomTotal,0,',','.') }}</td>
            </tr>
            <tr>
                <td>Total Cafe</td>
                <td class="right">Rp {{ number_format($cafeTotal,0,',','.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Grand Total</td>
                <td class="right">Rp {{ number_format($grandTotal,0,',','.') }}</td>
            </tr>
        </table>

        <div class="meta">
            Status Pembayaran: {{ strtoupper($order->payment_status ?? 'dp') }}
            @if(!empty($order->dp_percentage))
                (DP {{ $order->dp_percentage }}%)
            @endif
        </div>

        <div class="print-btn">
            <button onclick="window.print()">Print</button>
        </div>
        <div class="muted">Terima kasih.</div>
    </div>
</body>
</html>
