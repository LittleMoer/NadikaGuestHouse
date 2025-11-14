<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Booking Confirmation {{ isset($isMerged) && $isMerged ? ('Nota '.$bookingNumber.' ('.$mergeCount.' booking)') : ('#'.$order->id) }}</title>
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
    /* Two-column layout */
    .columns { display:flex; gap:12px; align-items:flex-start; }
    .col-left { flex: 1 1 60%; }
    .col-right { flex: 1 1 40%; }
    .terms { margin-top: 18px; font-size: 18px; }
    .terms h3 {
      font-size: 16px;
      color: red;
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
    .bottom-right-warning {
      position: absolute;
      bottom: 15px;
      right: 15px;
      text-align: right;
      font-weight: bold;
      font-size: 0.9rem;
      color: #d32f2f;
    }
    .page-break {
      page-break-after: always;
    }
    .copy-identifier {
      text-align: right; font-size: 1.2rem; font-weight: bold; color: #444; margin-bottom: 10px; border-bottom: 2px dashed #ccc; padding-bottom: 5px;
    }
    @media print {
      @page {
        size: A4 portrait;
        margin: 0.3cm; /* Add margin to prevent content being cut off */
      }
      body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        font-size: 16px; /* compact for A5 fit */
        line-height: 1.3;   /* slightly tighter */
        zoom: 0.90; /* scale to 85% for printing */
      }
      .wrap {
        max-width: none;
        margin: 0;
        padding: 0; /* remove all padding/margin */
        position: relative; /* Needed for absolute positioning of children */
      }
      .header h1 { font-size: 24px; }
      .header .address, .header .contact { font-size: 12px; }
      .wifi-info { font-size: 10px; margin-bottom: 6px; }
      .booking-info { padding: 6px 8px; margin: 6px 0; }
      .columns { gap: 6px; }
      .col-left { flex-basis: 62%; }
      .col-right { flex-basis: 38%; }
      .info-row { margin-bottom: 3px; }
      .info-label { width: 140px; }
      .summary { padding: 8px 10px; }
      .summary .row { margin-bottom: 4px; }
      .summary .row.total { padding-top: 6px; margin-top: 6px; }
      .terms { margin-top: 8px; font-size: 12px; }
      .terms h3 { font-size: 13px; margin-bottom: 6px; }
      .terms li { margin-bottom: 3px; }
      .signature { margin-top: 12px; }
      .sign-line { margin: 20px 0 6px; }
      .copy-identifier {
        font-size: 1rem;
        margin-bottom: 8px;
        padding-bottom: 4px;
      }
      .bottom-right-warning {
        font-size: 0.8rem;
        bottom: 5mm;
        right: 5mm;
        line-height: 1.2;
      }
    }
  </style>
</head>
<body>
  <!-- Salinan untuk Tamu -->
  <div class="wrap">
    <div class="copy-identifier">
      Bukti Pengembalian Deposit
    </div>

    <div class="wifi-info">
      <span style="color: red;">ID:{{ $order->formatted_id }}</span><br>
      PASSWORD WIFI ATAS: nginapdulu<br>
      Gedung belakang: nadikaguestb2025
    </div>

    <div class="header">
      <h1>NADIKA GUEST HOUSE</h1>
      <div class="syariah">syariah</div>
      <div class="address">JL. Kalipepe I no.1 ( Grand Panorama Raya )<br>Pudakpayung - SEMARANG</div>
      <div class="contact">Telpon: 024.7461127 - 08122542588</div>
    </div>

    <div class="columns">
      <div class="col-left">
        <div class="booking-info">
          <div class="section-title">Data Tamu & Booking</div>
          <div class="guest-info">
            <div class="info-row"><div class="info-label">Nama Pengunjung</div><div class="info-value">: {{ $order->pelanggan?->nama ?? '-' }}</div></div>
            <div class="info-row"><div class="info-label">No. Identitas/SIM</div><div class="info-value">: {{ $order->pelanggan?->no_identitas ?? '-' }}</div></div>
            <div class="info-row"><div class="info-label">No. HP</div><div class="info-value">: {{ $order->pelanggan?->telepon ?? '-' }}</div></div>
            @if(!empty($isMerged) && $isMerged)
              <div class="info-row"><div class="info-label">Nota</div><div class="info-value">: {{ $bookingNumber }} ({{ $mergeCount }} booking)</div></div>
            @else
              <div class="info-row"><div class="info-label">Check-in</div><div class="info-value">: {{ $order->tanggal_checkin->format('d/m/Y H:i') }} WIB</div></div>
              <div class="info-row"><div class="info-label">Check-out</div><div class="info-value">: {{ $order->tanggal_checkout->format('d/m/Y H:i') }} WIB</div></div>
            @endif
            <div class="info-row"><div class="info-label">Jumlah Tamu</div><div class="info-value">: {{ $order->jumlah_tamu_total ?? '0' }} orang</div></div>
            <div class="info-row"><div class="info-label">Jaminan</div><div class="info-value">: {{ $order->pelanggan?->jenis_identitas ?? '-' }}</div></div>
            <div class="info-row">
              <div class="info-label">Jenis Kamar Disewa</div>
              <div class="info-value">:
                @php
                  $listItems = (!empty($isMerged) && $isMerged) ? ($mergedItems ?? collect()) : collect($order->items);
                @endphp
                {{ $listItems->map(function($it){
                    $no = $it->kamar?->nomor_kamar ?? '-';
                    $tipe = $it->kamar?->tipe ?? '-';
                    return $no.' ('.$tipe.')';
                })->join(', ') }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-right">
        @php
          $isTraveloka = ((int)($order->pemesanan ?? 0)) === 1;
          if(!empty($isMerged) && $isMerged){
            $baseSubtotal = (int)($mergedBaseSubtotal ?? 0);
            $diskon = (int)($mergedDiskonTotal ?? 0);
            $paid = (int)($mergedPaidTotal ?? 0);
            $biayaTambahan = (int)($mergedBiayaTambahanTotal ?? 0);
          } else {
            $itemsSubtotal = (int) (collect($order->items)->sum('subtotal') ?? 0);
            $explicitTotal = (int) ($order->total_harga ?? 0);
            $baseSubtotal = $itemsSubtotal > 0 ? $itemsSubtotal : $explicitTotal;
            $diskon = (int) ($order->diskon ?? 0);
            $paid = (int) ($order->dp_amount ?? 0);
            $biayaTambahan = (int) ($order->biaya_tambahan ?? 0);
          }
          $totalAfterDiscount = max(0, $baseSubtotal - $diskon + ($biayaTambahan ?? 0));
          $remaining = max(0, $totalAfterDiscount - $paid);
        @endphp
        <div class="summary">
          <div class="section-title">Ringkasan Pembayaran {{ (!empty($isMerged) && $isMerged) ? '(Gabungan Nota '.$bookingNumber.')' : '' }}</div>
          @if(!$isTraveloka)
            <div class="row"><div class="label">Subtotal</div><div class="value">Rp {{ number_format($baseSubtotal,0,',','.') }}</div></div>
            @if($diskon > 0)
              <div class="row"><div class="label">Diskon</div><div class="value">- Rp {{ number_format($diskon,0,',','.') }}</div></div>
            @endif
            @if(($biayaTambahan ?? 0) > 0)
              <div class="row"><div class="label">Biaya Lain</div><div class="value">+ Rp {{ number_format($biayaTambahan,0,',','.') }}</div></div>
            @endif
            <div class="row total"><div class="label">Total</div><div class="value">Rp {{ number_format($totalAfterDiscount,0,',','.') }}</div></div>
            <div class="row"><div class="label">Sudah Dibayar</div><div class="value">Rp {{ number_format($paid,0,',','.') }}</div></div>
            <div class="row"><div class="label">Sisa</div><div class="value">Rp {{ number_format($remaining,0,',','.') }}</div></div>
          @else
            {{-- Untuk Traveloka, hanya tampilkan biaya langsung jika ada --}}
            @if(($biayaTambahan ?? 0) > 0)
              <div class="row total"><div class="label">Biaya Langsung</div><div class="value">Rp {{ number_format($biayaTambahan,0,',','.') }}</div></div>
              <div class="row"><div class="label">Sisa</div><div class="value">Rp {{ number_format($biayaTambahan,0,',','.') }}</div></div>
            @else
              <div class="row"><div class="label">Informasi</div><div class="value">- Tidak ada tagihan langsung -</div></div>
            @endif
          @endif
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

    <div class="bottom-right-warning">
      "Transaksi yang tidak dilampiri nota diskon 50%"<br>
      "JANGAN SAMPAI HILANG"
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

  <!-- Pemisah Halaman -->
  <div class="page-break"></div>

  <!-- Salinan untuk Resepsionis -->
  <div class="wrap">
    <div class="copy-identifier" style="color: #0d47a1;">
      SALINAN UNTUK RESEPSIONIS
    </div>

    <div class="wifi-info">
      <span style="color: red;">ID:{{ $order->formatted_id }}</span><br>
      PASSWORD WIFI ATAS: nginapdulu<br>
      Gedung belakang: nadikaguestb2025
    </div>

    <div class="header">
      <h1>NADIKA GUEST HOUSE</h1>
      <div class="syariah">syariah</div>
      <div class="address">JL. Kalipepe I no.1 ( Grand Panorama Raya )<br>Pudakpayung - SEMARANG</div>
      <div class="contact">Telpon: 024.7461127 - 08122542588</div>
    </div>

    <div class="columns">
      <div class="col-left">
        <div class="booking-info">
          <div class="section-title">Data Tamu & Booking</div>
          <div class="guest-info">
            <div class="info-row"><div class="info-label">Nama Pengunjung</div><div class="info-value">: {{ $order->pelanggan?->nama ?? '-' }}</div></div>
            <div class="info-row"><div class="info-label">No. Identitas/SIM</div><div class="info-value">: {{ $order->pelanggan?->no_identitas ?? '-' }}</div></div>
            <div class="info-row"><div class="info-label">No. HP</div><div class="info-value">: {{ $order->pelanggan?->telepon ?? '-' }}</div></div>
            @if(!empty($isMerged) && $isMerged)
              <div class="info-row"><div class="info-label">Nota</div><div class="info-value">: {{ $bookingNumber }} ({{ $mergeCount }} booking)</div></div>
            @else
              <div class="info-row"><div class="info-label">Check-in</div><div class="info-value">: {{ $order->tanggal_checkin->format('d/m/Y H:i') }} WIB</div></div>
              <div class="info-row"><div class="info-label">Check-out</div><div class="info-value">: {{ $order->tanggal_checkout->format('d/m/Y H:i') }} WIB</div></div>
            @endif
            <div class="info-row"><div class="info-label">Jumlah Tamu</div><div class="info-value">: {{ $order->jumlah_tamu_total ?? '0' }} orang</div></div>
            <div class="info-row"><div class="info-label">Jaminan</div><div class="info-value">: {{ $order->pelanggan?->jenis_identitas ?? '-' }}</div></div>
            <div class="info-row">
              <div class="info-label">Jenis Kamar Disewa</div>
              <div class="info-value">:
                @php
                  $listItems = (!empty($isMerged) && $isMerged) ? ($mergedItems ?? collect()) : collect($order->items);
                @endphp
                {{ $listItems->map(function($it){
                    $no = $it->kamar?->nomor_kamar ?? '-';
                    $tipe = $it->kamar?->tipe ?? '-';
                    return $no.' ('.$tipe.')';
                })->join(', ') }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-right">
        <div class="summary">
          <div class="section-title">Ringkasan Pembayaran {{ (!empty($isMerged) && $isMerged) ? '(Gabungan Nota '.$bookingNumber.')' : '' }}</div>
          @if(!$isTraveloka)
            <div class="row"><div class="label">Subtotal</div><div class="value">Rp {{ number_format($baseSubtotal,0,',','.') }}</div></div>
            @if($diskon > 0)
              <div class="row"><div class="label">Diskon</div><div class="value">- Rp {{ number_format($diskon,0,',','.') }}</div></div>
            @endif
            @if(($biayaTambahan ?? 0) > 0)
              <div class="row"><div class="label">Biaya Lain</div><div class="value">+ Rp {{ number_format($biayaTambahan,0,',','.') }}</div></div>
            @endif
            <div class="row total"><div class="label">Total</div><div class="value">Rp {{ number_format($totalAfterDiscount,0,',','.') }}</div></div>
            <div class="row"><div class="label">Sudah Dibayar</div><div class="value">Rp {{ number_format($paid,0,',','.') }}</div></div>
            <div class="row"><div class="label">Sisa</div><div class="value">Rp {{ number_format($remaining,0,',','.') }}</div></div>
          @else
            {{-- Untuk Traveloka, hanya tampilkan biaya langsung jika ada --}}
            @if(($biayaTambahan ?? 0) > 0)
              <div class="row total"><div class="label">Biaya Langsung</div><div class="value">Rp {{ number_format($biayaTambahan,0,',','.') }}</div></div>
              <div class="row"><div class="label">Sisa</div><div class="value">Rp {{ number_format($biayaTambahan,0,',','.') }}</div></div>
            @else
              <div class="row"><div class="label">Informasi</div><div class="value">- Tidak ada tagihan langsung -</div></div>
            @endif
          @endif
        </div>
      </div>
    </div>

    <div class="bottom-right-warning">
       "Yang tidak dilampiri nota diskon 50%"<br>
      "JANGAN SAMPAI HILANG"
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