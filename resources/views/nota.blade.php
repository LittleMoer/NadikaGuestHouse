<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nota {{ isset($isMerged) && $isMerged ? ('Nota '.$bookingNumber.' ('.$mergeCount.' booking)') : ('#'.$order->order_code) }}</title>
    <style>
        body {  font-family: Arial, sans-serif;margin: 16px; color:#111; font-size: 12px; }
        .nota { max-width: 320px; margin: 0 auto; }
        .wifi-info { text-align: right; font-size: 12px; color: #666; margin-bottom: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #d32f2f; font-size: 24px; margin: 0; font-weight: bold; }
        .header .syariah { color: #388e3c; font-style: italic; margin-top: -5px; }
        .header .address { font-size: 14px; margin-top: 5px; }
        .header .contact { font-size: 14px; margin-top: 5px; }
        .muted { color:#555; font-size:.8rem; }
        table { width:100%; border-collapse: collapse; margin-top:8px; }
        th, td { padding:2px 0; vertical-align: top; }
        thead th { font-weight: 600; border-bottom: 1px dashed #555; padding-bottom: 4px; }
        .right { text-align:right; }
        .divider { border-top:1px dashed #555; margin:8px 0; }
        .total-row td { font-weight:700; padding-top:4px; }
        .grand-total td { border-top:1px solid #000; padding-top: 6px; font-size: 1.1em; }
        .meta { margin-top:8px; font-size:.8rem; }
        .item-detail { font-size: .9em; color: #555; padding-left: 10px; }
        .signature-area { margin-top: 40px; display: flex; justify-content: space-between; font-size: 12px; }
        .signature-box { width: 45%; text-align: center; }
        .signature-line { margin-top: 50px; border-top: 1px solid #000; }
        .warning-text { text-align: center; font-weight: bold; margin: 15px 0; font-size: 1.1rem; color: #d32f2f; }
        .bottom-right-warning {
            text-align: right;
            color: #d32f2f;
            font-weight: bold;
            margin-top: 20px;
        }

        .print-btn { margin: 12px 0; }
        @media print { 
            .print-btn { display:none; }
            @page {
                size: A4 portrait;
                margin: 0.5cm; /* Set consistent margin */
            }
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                zoom: 0.95; /* Slightly scale down to ensure fit */
            }
            .nota {
                max-width: 100%;
                padding: 0; /* Remove padding as @page margin is used */
            }
        }
    </style>
</head>
<body>
    <div class="nota">
        <div class="wifi-info">
            ID: {{ $order->formatted_id }}<br>
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
                <tr>
                    <th style="text-align:left;">Rincian</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @if($roomItems->count() > 0)
                    @foreach($roomItems as $it)
                    <tr>
                        <td>Kamar {{ $it->kamar?->nomor_kamar }} ({{ $it->malam }} malam)</td>
                        <td class="right">Rp {{ number_format($it->subtotal,0,',','.') }}</td>
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
            @php
                // Gunakan accessor dari model BookingOrder
                $dpAmount = (int)($order->dp_amount ?? 0);
                $jumlahPelunasan = (int)($order->jumlah_pelunasan ?? 0);
                $sisaPembayaran = (int)($order->sisa_pembayaran ?? 0);

                // Untuk nota gabungan, kita pakai variabel dari controller
                if ($isMerged) {
                    $dpAmount = (int)$paidTotal;
                    $sisaPembayaran = (int)$remaining;
                    // Pelunasan di nota gabungan adalah selisih jika sudah lunas
                    $isLunasMerged = collect($siblings)->every(fn($s) => $s->payment_status === 'lunas');
                    $jumlahPelunasan = $isLunasMerged ? $sisaPembayaran : 0;
                }
            @endphp
            <tbody>
                <tr>
                    <td>Total Kamar & Cafe</td>
                    <td class="right">Rp {{ number_format($roomItems->sum('subtotal') + $cafeItems->sum('subtotal'), 0, ',', '.') }}</td>
                </tr>
                @if($diskon > 0)
                <tr>
                    <td>Diskon</td>
                    <td class="right">- Rp {{ number_format($diskon,0,',','.') }}</td>
                </tr>
                @endif
                @if($biayaLain > 0)
                    <tr>
                        <td>{{ $isTraveloka ? 'Biaya Langsung' : 'Biaya Tambahan' }}</td>
                        <td class="right">+ Rp {{ number_format($biayaLain,0,',','.') }}</td>
                    </tr>
                @endif

                <tr class="total-row grand-total">
                    <td>Total Tagihan</td>
                    <td class="right">Rp {{ number_format($grandTotal,0,',','.') }}</td>
                </tr>

                @if($dpAmount > 0 && $jumlahPelunasan > 0)
                    <tr>
                        <td>DP Dibayar</td>
                        <td class="right">Rp {{ number_format($dpAmount - $jumlahPelunasan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Pelunasan</td>
                        <td class="right">Rp {{ number_format($jumlahPelunasan, 0, ',', '.') }}</td>
                    </tr>
                @elseif($dpAmount > 0)
                <tr>
                    <td>DP Dibayar</td>
                    <td class="right">Rp {{ number_format($dpAmount, 0, ',', '.') }}</td>
                </tr>
                @endif

                <tr class="total-row grand-total">
                    <td>Sisa Pembayaran</td>
                    <td class="right">Rp {{ number_format($sisaPembayaran, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="meta">
            Status: {{ strtoupper($order->payment_status ?? 'dp') }}
        </div>

        <div class="terms">
            <div class="terms-title">Syarat & Ketentuan Menginap:</div>
            <div class="terms-columns">
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
        </div>
        <div class="bottom-right-warning">
        "Transaksi yang tidak dilampiri nota diskon 50%"<br>
        "JANGAN SAMPAI HILANG"
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
