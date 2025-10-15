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
      <div>
        <label for="payment_method" style="display:block;font-size:.8rem;color:#555;">Metode Bayar</label>
        @php $pm = strtolower($filter_payment_method ?? request('payment_method','all')); @endphp
        <select id="payment_method" name="payment_method" class="form-control">
          <option value="all" {{ ($pm==='all'||$pm==='') ? 'selected' : '' }}>Semua</option>
          <option value="cash" {{ $pm==='cash' ? 'selected' : '' }}>CASH</option>
          <option value="transfer" {{ $pm==='transfer' ? 'selected' : '' }}>TRANSFER</option>
          <option value="qris" {{ $pm==='qris' ? 'selected' : '' }}>QRIS</option>
          <option value="card" {{ $pm==='card' ? 'selected' : '' }}>CARD</option>
        </select>
      </div>
      <div>
        <label for="channel" style="display:block;font-size:.8rem;color:#555;">Channel</label>
        @php $ch = strtolower($filter_channel ?? request('channel','all')); @endphp
        <select id="channel" name="channel" class="form-control">
          <option value="all" {{ ($ch==='all'||$ch==='') ? 'selected' : '' }}>Semua</option>
          <option value="walkin" {{ $ch==='walkin' ? 'selected' : '' }}>Walk-In</option>
          <option value="traveloka" {{ $ch==='traveloka' ? 'selected' : '' }}>Traveloka</option>
          <option value="agent1" {{ $ch==='agent1' ? 'selected' : '' }}>Agent 1</option>
          <option value="agent2" {{ $ch==='agent2' ? 'selected' : '' }}>Agent 2</option>
        </select>
      </div>
      <div>
        <label for="discount" style="display:block;font-size:.8rem;color:#555;">Diskon</label>
        @php $ds = strtolower($filter_discount ?? request('discount','all')); @endphp
        <select id="discount" name="discount" class="form-control">
          <option value="all" {{ ($ds==='all'||$ds==='') ? 'selected' : '' }}>Semua</option>
          <option value="with" {{ $ds==='with' ? 'selected' : '' }}>Dengan Diskon</option>
          <option value="without" {{ $ds==='without' ? 'selected' : '' }}>Tanpa Diskon</option>
        </select>
      </div>
      <div>
        <label for="payment_status" style="display:block;font-size:.8rem;color:#555;">Status Pembayaran</label>
        @php $ps = strtolower($filter_payment_status ?? request('payment_status','all')); @endphp
        <select id="payment_status" name="payment_status" class="form-control">
          <option value="all" {{ ($ps==='all'||$ps==='') ? 'selected' : '' }}>Semua</option>
          <option value="dp" {{ $ps==='dp' ? 'selected' : '' }}>DP</option>
          <option value="lunas" {{ $ps==='lunas' ? 'selected' : '' }}>Lunas</option>
          <option value="dp_cancel" {{ $ps==='dp_cancel' ? 'selected' : '' }}>DP Batal</option>
        </select>
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
                <th style="width:110px;">ID Booking</th>
                <th>Nama Penginap</th>
                <th style="width:150px;">Created (Booking)</th>
                <th style="width:140px;">Check-In</th>
                <th style="width:140px;">Check-Out</th>
                <th style="min-width:120px;">No. Kamar</th>
                <th style="width:120px;">Metode Bayar</th>
                <th style="width:120px;">Metode Pesan</th>
                <th>Keterangan</th>
                <th class="text-end" style="width:160px;">Nominal Masuk</th>
                <th style="width:70px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            @php
              $mapType = ['dp_in'=>'DP Masuk','dp_remaining_in'=>'Pelunasan','cafe_in'=>'Cafe','cashback_in'=>'Cashback'];
              $mapPemesanan = [0=>'Walk-In',1=>'Online',2=>'Agent 1',3=>'Agent 2'];
              $i=1;
            @endphp
            @forelse($entries ?? [] as $e)
              <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $e->booking_id ? ('#'.($e->booking_number ?? $e->booking_id)) : '-' }}</td>
                <td>{{ $e->pelanggan_nama ?? '-' }}</td>
                <td>{{ $e->booking_created_at ? \Carbon\Carbon::parse($e->booking_created_at)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $e->tanggal_checkin ? \Carbon\Carbon::parse($e->tanggal_checkin)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $e->tanggal_checkout ? \Carbon\Carbon::parse($e->tanggal_checkout)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $e->room_numbers ?? '-' }}</td>
                <td>{{ strtoupper($e->payment_method ?? '-') }}</td>
                <td>{{ $mapPemesanan[$e->pemesanan ?? 0] ?? '-' }}</td>
                <td>{{ $mapType[$e->type] ?? ucfirst($e->type) }}{{ $e->note ? (' - '.$e->note) : '' }}</td>
                <td class="text-end">Rp {{ number_format((int)($e->display_amount ?? (int)$e->amount),0,',','.') }}</td>
                <td>
                  <form method="POST" action="{{ route('rekap.destroy', $e->ledger_id) }}" onsubmit="return confirm('Hapus pemasukan ini dari rekap?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="bulan" value="{{ $bulan }}" />
                    <input type="hidden" name="tahun" value="{{ $tahun }}" />
                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                </td>
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
