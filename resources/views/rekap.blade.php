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
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm">
            <thead class="table-light">
              <tr>
                <th style="width:50px;">No</th>
                <th style="width:80px;">ID Booking</th>
                <th>Nama Penginap</th>
                <th style="width:120px;">Metode Bayar</th>
                <th style="width:120px;">Metode Pesan</th>
                <th>Keterangan</th>
                <th class="text-end" style="width:160px;">Nominal Masuk</th>
              </tr>
            </thead>
            <tbody>
            @php
              $mapType = ['dp_in'=>'DP Masuk','dp_remaining_in'=>'Pelunasan','cafe_in'=>'Cafe'];
              $mapPemesanan = [0=>'Walk-In',1=>'Online',2=>'Agent 1',3=>'Agent 2'];
              $i=1;
            @endphp
            @forelse($entries ?? [] as $e)
              <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $e->booking_id ? ('#'.$e->booking_id) : '-' }}</td>
                <td>{{ $e->pelanggan_nama ?? '-' }}</td>
                <td>{{ strtoupper($e->payment_method ?? '-') }}</td>
                <td>{{ $mapPemesanan[$e->pemesanan ?? 0] ?? '-' }}</td>
                <td>{{ $mapType[$e->type] ?? ucfirst($e->type) }}{{ $e->note ? (' - '.$e->note) : '' }}</td>
                <td class="text-end">Rp {{ number_format((int)$e->amount,0,',','.') }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted">Tidak ada pemasukan pada periode ini.</td></tr>
            @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th colspan="6" class="text-end">Total Pemasukan Bulan Ini</th>
                <th class="text-end">Rp {{ number_format((int)($cashGrand ?? 0),0,',','.') }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
