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
        th, td { padding:2px 0; font-size:.85rem; vertical-align: top; }
        thead th { font-weight: 600; border-bottom: 1px solid #ddd; }
        .right { text-align:right; }
        .divider { border-top:1px dashed #999; margin:8px 0; }
        .total-row td { font-weight:700; border-top:1px solid #000; padding-top:8px; }
        .meta { margin-top:8px; font-size:.8rem; }
        .terms { margin-top: 18px; font-size: .8rem; }
        .terms h3 { font-size: .9rem; margin-bottom: 8px; font-weight: bold; color: red; }
        .terms ul { padding-left: 18px; margin: 0; }
        .terms li { margin-bottom: 4px; }
        .item-detail { font-size: .75rem; color: #555; padding-left: 10px; }

        .print-btn { margin: 12px 0; }
        @media print { 
            .print-btn { display:none; }
            @page {
                size: A4 portrait;
                margin: 0;
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
        <div style="text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 1.1rem; color: #d32f2f;">
            "makan tanpa nota akan dapat diskon"
        </div>

        <div class="wifi-info">
            ID:{{ $order->formatted_id }}<br>
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
        @if(!empty($order->catatan))
        <div class="muted">Catatan: {{ $order->catatan }}</div>
        @endif

        <div class="divider"></div>
        <table>
            <thead>
                <tr><th colspan="2" style="text-align:left;padding-bottom:4px;">Rincian</th></tr>
            </thead>
            <tbody>
                @if($roomItems->count() > 0)
                    @foreach($roomItems as $it)
                    <tr>
                        <td>Kamar {{ $it->kamar?->nomor_kamar }} ({{ $it->malam }} malam)</td>
                        <td class="right">Rp {{ number_format($it->subtotal,0,',','.') }}</td>
                    </tr>
                    <tr class="item-detail-row">
                        <td colspan="2" class="item-detail">
                            {{ $it->kamar?->tipe }} | {{ \Carbon\Carbon::parse($it->order?->tanggal_checkin)->format('d/m H:i') }} - {{ \Carbon\Carbon::parse($it->order?->tanggal_checkout)->format('d/m H:i') }}
                        </td>
                    </tr>
                    @endforeach
                @endif
                @if($cafeItems->count() > 0)
                    @foreach($cafeItems as $it)
                    <tr>
                        <td>Cafe: {{ $it->product?->nama }} ({{ $it->qty }}x)</td>
                        <td class="right">Rp {{ number_format($it->subtotal,0,',','.') }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        <div class="divider"></div>
        <table>
            <tbody>
                <tr>
                    <td>Subtotal</td>
                    <td class="right">Rp {{ number_format($subtotal,0,',','.') }}</td>
                </tr>
                @if($diskon > 0)
                <tr>
                    <td>Diskon</td>
                    <td class="right">- Rp {{ number_format($diskon,0,',','.') }}</td>
                </tr>
                @endif
                @if($biayaLain > 0)
                <tr>
                    <td>{{ isset($isTraveloka) && $isTraveloka ? 'Biaya Langsung' : 'Biaya Lain' }}</td>
                    <td class="right">+ Rp {{ number_format($biayaLain,0,',','.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total Akhir</td>
                    <td class="right">Rp {{ number_format($grandTotal,0,',','.') }}</td>
                </tr>
                @if($paidTotal > 0)
                <tr>
                    <td>Sudah Dibayar</td>
                    <td class="right">Rp {{ number_format($paidTotal,0,',','.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Sisa Pembayaran</td>
                    <td class="right">Rp 
                        @if(isset($isTraveloka) && $isTraveloka)
                            {{ number_format($remaining + $biayaLain,0,',','.') }}
                        @else
                            {{ number_format($remaining,0,',','.') }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="meta">
            Status Pembayaran: {{ strtoupper($order->payment_status ?? 'dp') }}
            @if(!empty($order->dp_percentage))
                (DP {{ $order->dp_percentage }}%)
            @endif
        </div>

        <div class="terms">
            <h3>Syarat & Ketentuan Menginap:</h3>
            <ul>
                <li>Check-in dimulai pukul 12:00 WIB dan check-out maksimal pukul 12:00 WIB.</li>
                <li>Tamu wajib menunjukkan identitas asli (KTP/SIM) yang masih berlaku saat check-in.</li>
                <li>Dilarang membawa tamu tambahan ke dalam kamar tanpa registrasi.</li>
                <li>Barang berharga harap dijaga sendiri, pihak guest house tidak bertanggung jawab atas kehilangan.</li>
                <li>Kerusakan atau kehilangan fasilitas kamar akan dikenakan biaya sesuai ketentuan.</li>
                <li>Dilarang merokok di dalam kamar.</li>
                <li>Mohon jaga ketenangan dan tidak membuat gaduh.</li>
                <li>Guest house berhak membatalkan reservasi jika tamu melanggar ketentuan.</li>
            </ul>
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
