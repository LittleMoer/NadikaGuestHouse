<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nota Booking {{ isset($isMerged) && $isMerged ? ('Nota '.$bookingNumber.' ('.$mergeCount.' booking)') : ('#'.$order->id) }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color:#111; }
        .nota { max-width: 420px; margin: 0 auto; }
        .wifi-info { text-align: right; font-size: 12px; color: #666; margin-bottom: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #d32f2f; font-size: 24px; margin: 0; font-weight: bold; }
        .header .syariah { color: #388e3c; font-style: italic; margin-top: -5px; }
        .header .address { font-size: 14px; margin-top: 5px; }
        .header .contact { font-size: 14px; margin-top: 5px; }
        .muted { color:#555; font-size:.8rem; }
        table { width:100%; border-collapse: collapse; margin-top:10px; }
        th, td { padding:6px 0; font-size:.9rem; }
        thead th { font-weight: 600; border-bottom: 1px solid #ddd; }
        .right { text-align:right; }
        .divider { border-top:1px dashed #999; margin:8px 0; }
        .total-row td { font-weight:700; border-top:1px solid #000; padding-top:8px; }
        .meta { margin-top:8px; font-size:.8rem; }
        .print-btn { margin: 12px 0; }
        @media print { 
            .print-btn { display:none; }
            @page {
                size: A5 landscape;
                margin: 10mm;
            }
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
            }
            .nota {
                max-width: none;
                margin: 0;
                padding: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="nota">
        <div class="wifi-info">
            ID:{{ $order->id }}{{ now()->format('Ymd') }}<br>
            PASSWORD WIFI ATAS: nginapdulu<br>
            Gedung belakang: nadikaguestb2025
        </div>

        <div class="header">
            <h1>NADIKA GUEST HOUSE</h1>
            <div class="syariah">syariah</div>
            <div class="address">JL. Kalipepe I no.1 ( Grand Panorama Raya )<br>Pudakpayung - SEMARANG</div>
            <div class="contact">Telpon: 024.7461127 - 08122542588</div>
        </div>

        <div class="muted">Tanggal: {{ now()->format('d/m/Y H:i') }}</div>
        @if(!empty($isMerged) && $isMerged)
        <div class="muted">Nota: {{ $bookingNumber }} ({{ $mergeCount }} booking)</div>
        @endif
        <div class="muted">Pelanggan: {{ $order->pelanggan?->nama ?? '-' }} ({{ $order->pelanggan?->telepon ?? '-' }})</div>
        <div class="muted">Check-in: {{ $order->tanggal_checkin->format('d/m/Y H:i') }}</div>
        <div class="muted">Check-out: {{ $order->tanggal_checkout->format('d/m/Y H:i') }}</div>
        <div class="muted">Jumlah Tamu: {{ $order->jumlah_tamu_total ?? '-' }}</div>

        <div class="divider"></div>
        <table>
            <thead>
                <tr>
                    <th style="text-align:left;">Item</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Kamar</td>
                    <td class="right">Rp {{ number_format($roomTotal,0,',','.') }}</td>
                </tr>
                <tr>
                    <td>Total Cafe</td>
                    <td class="right">Rp {{ number_format($cafeTotal,0,',','.') }}</td>
                </tr>
                <tr>
                    <td>Total Sebelum Diskon</td>
                    <td class="right">Rp {{ number_format($roomTotal + $cafeTotal,0,',','.') }}</td>
                </tr>
                <tr>
                    <td>Diskon</td>
                    <td class="right">Rp {{ number_format($diskon,0,',','.') }}</td>
                </tr>
                <tr>
                    <td>Biaya Lain</td>
                    <td class="right">Rp {{ number_format($biayaLain,0,',','.') }}
                </tr>
                <tr class="total-row">
                    <td>Total Akhir</td>
                    <td class="right">Rp {{ number_format($grandTotal,0,',','.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="meta">
            Status Pembayaran: {{ strtoupper($order->payment_status ?? 'dp') }}
            @if(!empty($order->dp_percentage))
                (DP {{ $order->dp_percentage }}%)
            @endif
        </div>

        <script>
            window.addEventListener('load', function() {
                setTimeout(function() { window.print(); }, 500);
            });
        </script>
        <div class="muted">Terima kasih.</div>
    </div>
</body>
</html>
