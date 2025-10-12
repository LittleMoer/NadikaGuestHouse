<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Booking Confirmation #{{ $order->id }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 16px;
      color: #111;
      line-height: 1.5;
      font-size: 16px;
    }
    .wrap {
      max-width: 600px;
      margin: 0 auto;
    }
    .header {
      text-align: center;
      margin-bottom: 20px;
    }
    .header h1 {
      color: #d32f2f;
      font-size: 24px;
      margin: 0;
      font-weight: bold;
    }
    .header .syariah {
      color: #388e3c;
      font-style: italic;
      margin-top: -5px;
    }
    .header .address {
      font-size: 14px;
      margin-top: 5px;
    }
    .header .contact {
      font-size: 14px;
      margin-top: 5px;
    }
    .wifi-info {
      text-align: right;
      font-size: 12px;
      color: #666;
      margin-bottom: 10px;
    }
    .booking-info {
      border: 1px solid #ccc;
      padding: 14px 16px;
      border-radius: 8px;
      margin: 14px 0;
      background: #fafafa;
    }
    .section-title { font-weight: 700; margin: 0 0 10px; font-size: 1.1em; }
    .guest-info { margin-bottom: 10px; }
    .info-row { display: flex; margin-bottom: 6px; align-items: baseline; }
    .info-label { width: 180px; color: #333; font-weight: 600; }
    .info-value { flex: 1; }
    .divider { height: 1px; background: #e6e6e6; margin: 10px 0; }
    .summary {
      border: 1px solid #ccc; border-radius: 8px; padding: 12px 14px; background: #fff;
    }
    .summary .row { display:flex; justify-content:space-between; margin-bottom:6px; }
    .summary .row .label { color:#333; }
    .summary .row .value { font-weight:600; }
    .summary .row.total { border-top:1px dashed #ddd; padding-top:8px; margin-top:8px; }
    .terms { margin-top: 18px; font-size: 18px; }
    .terms h3 {
      font-size: 16px;
      margin-bottom: 10px;
    }
    .terms ul {
      padding-left: 20px;
      margin: 0;
    }
    .terms li {
      margin-bottom: 5px;
    }
    .signature {
      margin-top: 30px;
      display: flex;
      justify-content: space-between;
    }
    .sign-box {
      width: 45%;
      text-align: center;
    }
    .sign-line {
      margin: 50px 0 10px;
      border-top: 1px solid #000;
    }
    @media print {
      @page {
        size: A5 landscape;
        margin: 0; /* match 'Margins: None' */
      }
      body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        font-size: 22px; /* bigger for readability */
        line-height: 1.36;   /* keep compact */
        zoom: 1; /* ensure normal scaling for print */
      }
      .wrap {
        max-width: none;
        margin: 0;
        padding: 6mm; /* inner breathing space since outer margin is 0 */
      }
      .header h1 { font-size: 34px; }
      .header .address, .header .contact { font-size: 18px; }
      .wifi-info { font-size: 16px; margin-bottom: 8px; }
      .booking-info { padding: 10px 12px; margin: 10px 0; }
      .guest-info { margin-bottom: 8px; }
      .info-row { margin-bottom: 4px; }
      .terms { margin-top: 12px; font-size: 18px; }
      .terms h3 { font-size: 20px; margin-bottom: 8px; }
      .terms li { margin-bottom: 4px; }
      .signature { margin-top: 18px; }
      .sign-line { margin: 32px 0 8px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="wifi-info">
      ID:{{ now()->format('Ym') }}{{ $order->id }}<br>
      PASSWORD WIFI ATAS: nginapdulu<br>
      Gedung belakang: nadikaguestb2025
    </div>

    <div class="header">
      <h1>NADIKA GUEST HOUSE</h1>
      <div class="syariah">syariah</div>
      <div class="address">JL. Kalipepe I no.1 ( Grand Panorama Raya )<br>Pudakpayung - SEMARANG</div>
      <div class="contact">Telpon: 024.7461127 - 08122542588</div>
    </div>

    <div class="booking-info">
      <div class="guest-info">
        <div class="info-row">
          <div class="info-label">Nama Pengunjung</div>
          <div>: {{ $order->pelanggan?->nama ?? '-' }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">No. Identitas/SIM</div>
          <div>: {{ $order->pelanggan?->no_identitas ?? '-' }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">No. HP</div>
          <div>: {{ $order->pelanggan?->telepon ?? '-' }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Check-in</div>
          <div>: {{ $order->tanggal_checkin->format('d/m/Y H:i') }} WIB</div>
        </div>
        <div class="info-row">
          <div class="info-label">Check-out</div>
          <div>: {{ $order->tanggal_checkout->format('d/m/Y H:i') }} WIB</div>
        </div>
        <div class="info-row">
          <div class="info-label">Jumlah Tamu</div>
          <div>: {{ $order->jumlah_tamu_total ?? '0' }} orang</div>
        </div>
        <div class="info-row">
          <div class="info-label">Jaminan</div>
          <div>: {{ $order->pelanggan?->jenis_identitas ?? '-' }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Jenis Kamar Disewa</div>
          <div>:
            {{ collect($order->items)->map(function($it){
                $no = $it->kamar?->nomor_kamar ?? '-';
                $tipe = $it->kamar?->tipe ?? '-';
                return $no.' ('.$tipe.')';
            })->join(', ') }}
          </div>
        </div>
        @php
          $itemsSubtotal = (int) (collect($order->items)->sum('subtotal') ?? 0);
          $explicitTotal = (int) ($order->total_harga ?? 0);
          // Prefer items subtotal when available; fallback to total_harga
          $baseSubtotal = $itemsSubtotal > 0 ? $itemsSubtotal : $explicitTotal;
          $diskon = (int) ($order->diskon ?? 0);
          $totalAfterDiscount = max(0, $baseSubtotal - $diskon);
          $paid = (int) ($order->dp_amount ?? 0);
          $remaining = max(0, $totalAfterDiscount - $paid);
        @endphp
        <div class="info-row">
          <div class="info-label">Subtotal</div>
          <div class="info-value">: Rp {{ number_format($baseSubtotal, 0, ',', '.') }}</div>
        </div>
        @if($diskon > 0)
        <div class="info-row">
          <div class="info-label">Diskon</div>
          <div class="info-value">: Rp {{ number_format($diskon, 0, ',', '.') }}</div>
        </div>
        @endif
        <div class="info-row">
          <div class="info-label">Total Harga Booking</div>
          <div class="info-value">: Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Sudah Dibayarkan</div>
          <div class="info-value">: Rp {{ number_format($paid, 0, ',', '.') }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Sisa Pembayaran</div>
          <div class="info-value">: Rp {{ number_format($remaining, 0, ',', '.') }}</div>
        </div>
      </div>
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
  </div>
  <script>
    // Auto-open print dialog when the page loads
    window.addEventListener('load', function(){
      // Small delay ensures fonts/layout are ready before printing
      setTimeout(function(){
        try { window.print(); } catch(e) {}
      }, 150);
    });
  </script>
</body>
</html>