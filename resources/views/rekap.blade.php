@extends('layouts.app_layout')

@section('rekap')
<div class="container">
  <div class="page-inner">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <h1 class="h1" style="margin:0;">Rekap Pemasukan Bulanan</h1>
    </div>

  <form method="GET" action="{{ route('rekap.index') }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-bottom:14px;">
      <div>
        <label for="bulan" style="display:block;font-size:.8rem;color:#555;">Bulan</label>
        <select id="bulan" name="bulan" class="form-control">
          @php $blnMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember']; @endphp
          @for($b=1;$b<=12;$b++)
            <option value="{{ $b }}" {{ (int)$bulan === $b ? 'selected' : '' }}>{{ $blnMap[$b] }}</option>
          @endfor
        </select>
      </div>
      <div>
        <label for="tahun" style="display:block;font-size:.8rem;color:#555;">Tahun</label>
        <input id="tahun" type="number" name="tahun" value="{{ $tahun }}" class="form-control" style="min-width:120px;" />
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary" style="margin-top:22px;">Tampilkan</button>
        <a class="btn btn-outline-secondary" style="margin-top:22px;" href="{{ route('rekap.print', ['bulan'=> request('bulan',$bulan), 'tahun'=> request('tahun',$tahun)]) }}" target="_blank">Cetak</a>
      </div>
    </form>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title" style="margin-bottom:10px;">Periode: {{ $blnMap[(int)$bulan] }} {{ $tahun }}</h5>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;max-width:720px;">
          <div class="alert alert-info"><strong>Total Kamar:</strong><br>Rp {{ number_format($totalKamar,0,',','.') }}</div>
          <div class="alert alert-success"><strong>Total Cafe:</strong><br>Rp {{ number_format($totalCafe,0,',','.') }}</div>
          <div class="alert alert-warning"><strong>Grand Total:</strong><br>Rp {{ number_format($grandTotal,0,',','.') }}</div>
        </div>

        <hr />
        <h6 style="margin-top:8px;">Detail Booking (header)</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Pelanggan</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Channel</th>
                <th>Total Harga</th>
              </tr>
            </thead>
            <tbody>
            @forelse($orders as $o)
              @php $meta = $o->status_meta; @endphp
              <tr>
                <td>{{ $o->id }}</td>
                <td>{{ optional($o->pelanggan)->nama ?? '-' }}</td>
                <td>{{ optional($o->tanggal_checkin)->format('Y-m-d H:i') }}</td>
                <td>{{ optional($o->tanggal_checkout)->format('Y-m-d H:i') }}</td>
                <td>{{ $o->status }}</td>
                <td>{{ strtoupper($o->payment_status ?? '-') }}</td>
                <td>{{ ucfirst($meta['channel'] ?? '-') }}</td>
                <td>Rp {{ number_format($o->total_harga ?? 0,0,',','.') }}</td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted">Tidak ada data</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
