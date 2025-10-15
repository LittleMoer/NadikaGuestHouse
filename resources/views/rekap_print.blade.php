<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Rekap Bulanan</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color:#111827; }
    .wrap { max-width: 900px; margin: 0 auto; padding: 12px; }
    h1 { font-size: 20px; margin: 0 0 6px; }
    .meta { font-size: 12px; color:#6b7280; margin-bottom: 10px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; margin: 10px 0 14px; }
    .card { border:1px solid #d1d5db; padding:10px; border-radius:6px; }
    .card h3 { margin:0 0 4px; font-size:14px; color:#374151; }
    .amount { font-weight:700; font-size:16px; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #d1d5db; padding:6px 8px; font-size:12px; text-align:left; }
    thead th { background:#f3f4f6; }
    .text-right { text-align:right; }
    @media print { 
      .no-print { display:none !important; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="no-print" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:8px;">
      <a href="{{ route('rekap.index', ['bulan'=>$bulan, 'tahun'=>$tahun]) }}" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;text-decoration:none;">Kembali</a>
      <button onclick="window.print()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;">Print</button>
    </div>
    @php $blnMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember']; @endphp
    <h1>Rekap Pemasukan Bulanan</h1>
    <div class="meta">Periode: <strong>{{ $blnMap[(int)$bulan] }} {{ $tahun }}</strong> &middot; Dicetak: {{ optional($printedAt)->format('Y-m-d H:i') }}</div>
    <div class="grid">
      <div class="card">
        <h3>Total Kamar</h3>
        <div class="amount">Rp {{ number_format($totalKamar,0,',','.') }}</div>
      </div>
      <div class="card">
        <h3>Total Cafe</h3>
        <div class="amount">Rp {{ number_format($totalCafe,0,',','.') }}</div>
      </div>
      <div class="card">
        <h3>Grand Total</h3>
        <div class="amount">Rp {{ number_format($grandTotal,0,',','.') }}</div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Pelanggan</th>
          <th style="width:140px;">Check-in</th>
          <th style="width:140px;">Check-out</th>
          <th style="width:70px;">Status</th>
          <th style="width:70px;">Payment</th>
          <th style="width:90px;">Channel</th>
          <th class="text-right" style="width:140px;">Total Harga</th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          @php $meta = $o->status_meta; @endphp
          <tr>
            <td>{{ $o->booking_number ?? $o->id }}</td>
            <td>{{ optional($o->pelanggan)->nama ?? '-' }}</td>
            <td>{{ optional($o->tanggal_checkin)->format('Y-m-d H:i') }}</td>
            <td>{{ optional($o->tanggal_checkout)->format('Y-m-d H:i') }}</td>
            <td>{{ $o->status }}</td>
            <td>{{ strtoupper($o->payment_status ?? '-') }}</td>
            <td>{{ ucfirst($meta['channel'] ?? '-') }}</td>
            <td class="text-right">Rp {{ number_format($o->total_harga ?? 0,0,',','.') }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-right">Tidak ada data</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <script>
    // Auto print on load (optional). Uncomment if desired.
    // window.addEventListener('load', () => setTimeout(() => window.print(), 300));
  </script>
</body>
</html>