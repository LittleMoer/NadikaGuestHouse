<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nota Cafe Booking #{{ $order->id }}</title>
  <style>
    body{font-family:Arial, sans-serif; margin:16px; color:#111;}
    .wrap{max-width:600px;margin:0 auto;}
    .wifi-info { text-align: right; font-size: 12px; color: #666; margin-bottom: 10px; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { color: #d32f2f; font-size: 24px; margin: 0; font-weight: bold; }
    .header .syariah { color: #388e3c; font-style: italic; margin-top: -5px; }
    .header .address { font-size: 14px; margin-top: 5px; }
    .header .contact { font-size: 14px; margin-top: 5px; }
    .muted{color:#555;font-size:.85rem;}
    table{width:100%;border-collapse:collapse;margin-top:10px;}
    th,td{padding:6px 0;font-size:.9rem;}
    .right{text-align:right;}
    .divider{border-top:1px dashed #999;margin:8px 0;}
    .total-row td{font-weight:700;border-top:1px solid #000;padding-top:8px;}
    .controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px;}
    .print-btn{margin:12px 0;}
    @media print {
      .print-btn,.controls{display:none;}
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
      .wrap {
        max-width: none;
        margin: 0;
        padding: 10mm;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">
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
        <div class="muted">Pelanggan: {{ $order->pelanggan?->nama ?? '-' }} ({{ $order->pelanggan?->telepon ?? '-' }})</div>

    <div class="divider"></div>
    <table>
      <thead>
        <tr>
          <th style="text-align:left;">Produk</th>
          <th class="right">Qty</th>
          <th class="right">Harga</th>
          <th class="right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @php $cafeTotal = 0; @endphp
        @forelse($cafeItems as $it)
          @php $sub = (float)$it->subtotal; $cafeTotal += $sub; @endphp
          <tr>
            <td>{{ $it->product?->nama ?? 'Item' }}</td>
            <td class="right">{{ $it->qty }}</td>
            <td class="right">{{ number_format($it->harga_satuan,0,',','.') }}</td>
            <td class="right">{{ number_format($sub,0,',','.') }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted">Belum ada order cafe.</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td colspan="3">Total Cafe</td>
          <td class="right" id="total_cafe">{{ number_format($cafeTotal,0,',','.') }}</td>
        </tr>
        <tr class="total-row">
          <td colspan="3">Total Akhir</td>
          <td class="right" id="total_akhir">{{ number_format($cafeTotal,0,',','.') }}</td>
        </tr>
      </tfoot>
    </table>

    <!-- Print dialog will open automatically -->
  </div>

  <script>
    window.addEventListener('load', function() {
      setTimeout(function() { window.print(); }, 500);
    });
  </script>
</body>
</html>
