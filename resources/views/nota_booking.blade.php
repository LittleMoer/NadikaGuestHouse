<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nota Booking #{{ $order->id }}</title>
  <style>
    body{font-family:Arial, sans-serif; margin:16px; color:#111;}
    .wrap{max-width:600px;margin:0 auto;}
    h1{font-size:1.15rem;margin:0 0 6px;}
    .muted{color:#555;font-size:.85rem;}
    table{width:100%;border-collapse:collapse;margin-top:10px;}
    th,td{padding:6px 0;font-size:.9rem;}
    .right{text-align:right;}
    .divider{border-top:1px dashed #999;margin:8px 0;}
    .total-row td{font-weight:700;border-top:1px solid #000;padding-top:8px;}
    .controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px;}
    input[type="number"], input[type="text"]{padding:6px 8px;border:1px solid #ccc;border-radius:6px;}
    .print-btn{margin:12px 0;}
    @media print{.print-btn,.controls{display:none;}}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Nota Booking #{{ $order->id }}</h1>
    <div class="muted">Tanggal: {{ now()->format('d/m/Y H:i') }}</div>
    <div class="muted">Pelanggan: {{ $order->pelanggan?->nama ?? '-' }} ({{ $order->pelanggan?->telepon ?? '-' }})</div>
    <div class="muted">Check-in: {{ $order->tanggal_checkin->format('d/m/Y H:i') }}</div>
    <div class="muted">Check-out: {{ $order->tanggal_checkout->format('d/m/Y H:i') }}</div>
    <div class="muted">Jumlah Tamu: {{ $order->jumlah_tamu_total ?? '-' }}</div>

    <div class="divider"></div>
    <table>
      <thead>
        <tr>
          <th style="text-align:left;">Kamar</th>
          <th class="right">Malam</th>
          <th class="right">Harga/Mlm</th>
          <th class="right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @php $roomTotal = 0; @endphp
        @foreach($order->items as $it)
          @php $roomTotal += (float)$it->subtotal; @endphp
          <tr>
            <td>{{ $it->kamar?->nomor_kamar }} ({{ $it->kamar?->tipe }})</td>
            <td class="right">{{ $it->malam }}</td>
            <td class="right">{{ number_format($it->harga_per_malam,0,',','.') }}</td>
            <td class="right">{{ number_format($it->subtotal,0,',','.') }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td colspan="3">Total Kamar</td>
          <td class="right" id="total_kamar">{{ number_format($roomTotal,0,',','.') }}</td>
        </tr>
        <tr>
          <td colspan="2">Diskon (Rp)</td>
          <td colspan="2" class="right"><input type="number" id="diskon" min="0" step="1000" value="0"/></td>
        </tr>
        <tr>
          <td colspan="2">Biaya Lain (Rp)</td>
          <td colspan="2" class="right"><input type="number" id="biaya_lain" min="0" step="1000" value="0"/></td>
        </tr>
        <tr class="total-row">
          <td colspan="3">Total Akhir</td>
          <td class="right" id="total_akhir">{{ number_format($roomTotal,0,',','.') }}</td>
        </tr>
      </tfoot>
    </table>

    <div class="controls">
      <label>Catatan:</label>
      <input type="text" id="catatan" placeholder="tuliskan catatan di nota" style="flex:1 1 300px;"/>
    </div>

    <div class="print-btn">
      <button onclick="window.print()">Print</button>
    </div>
  </div>

  <script>
    (function(){
      const fmt = n => new Intl.NumberFormat('id-ID').format(n||0);
      const parseNum = v => { const n = Number((v||'').toString().replace(/[^0-9.-]/g,'')); return Number.isFinite(n)? n: 0; };
      const totalKamar = parseNum(document.getElementById('total_kamar').textContent);
      const elDiskon = document.getElementById('diskon');
      const elBiaya = document.getElementById('biaya_lain');
      const elAkhir = document.getElementById('total_akhir');
      function recalc(){
        const d = Math.min(parseNum(elDiskon.value), totalKamar);
        const b = parseNum(elBiaya.value);
        const akhir = Math.max(totalKamar - d + b, 0);
        elAkhir.textContent = fmt(akhir);
      }
      elDiskon.addEventListener('input', recalc);
      elBiaya.addEventListener('input', recalc);
    })();
  </script>
</body>
</html>
