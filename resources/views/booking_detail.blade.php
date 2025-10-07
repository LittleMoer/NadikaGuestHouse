@extends('layouts.app_layout')

@section('booking')
<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <h4 class="page-title">Detail Booking #{{ $order->id }}</h4>
      <ul class="breadcrumbs">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="{{ route('booking.index') }}">Booking</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Detail</a></li>
      </ul>
    </div>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div><strong>Status:</strong> {{ $order->status_meta['label'] ?? '-' }}</div>
            <div><strong>Pembayaran:</strong> {{ strtoupper($order->payment_status ?? 'dp') }} @if($order->dp_percentage) ({{ $order->dp_percentage }}%) @endif</div>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-warning text-white" href="{{ route('booking.edit', $order->id) }}">Edit</a>
            <a class="btn btn-info" target="_blank" href="{{ route('booking.printout', $order->id) }}">Print Out</a>
            <a class="btn btn-primary" target="_blank" href="{{ route('booking.nota', $order->id) }}">Nota</a>
            <a class="btn btn-success" target="_blank" href="{{ route('booking.nota.cafe', $order->id) }}">Nota Cafe</a>
            @if(($order->payment_status ?? 'dp') !== 'lunas')
            <form action="{{ route('booking.payment', $order->id) }}" method="POST" class="d-inline">
              @csrf
              <input type="hidden" name="payment_status" value="lunas" />
              <button type="submit" class="btn btn-outline-success" title="Tandai pembayaran sebagai Lunas">Set Lunas</button>
            </form>
            @endif
            @if((int)$order->status === 2)
            <form action="{{ route('booking.status', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Checkout booking ini? Pembayaran akan ditandai Lunas.');">
              @csrf
              <input type="hidden" name="action" value="checkout" />
              <button type="submit" class="btn btn-dark" title="Ubah status menjadi Checkout">Checkout</button>
            </form>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="card mb-3">
          <div class="card-header">Informasi Utama</div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-4">ID</dt><dd class="col-8">#{{ $order->id }}</dd>
              <dt class="col-4">Pelanggan</dt><dd class="col-8">{{ $order->pelanggan->nama ?? '-' }} ({{ $order->pelanggan->telepon ?? '-' }})</dd>
              <dt class="col-4">Check-In</dt><dd class="col-8">{{ \Carbon\Carbon::parse($order->tanggal_checkin)->format('d/m/Y H:i') }}</dd>
              <dt class="col-4">Check-Out</dt><dd class="col-8">{{ \Carbon\Carbon::parse($order->tanggal_checkout)->format('d/m/Y H:i') }}</dd>
              <dt class="col-4">Jumlah Tamu</dt><dd class="col-8">{{ $order->jumlah_tamu_total ?? '-' }}</dd>
              <dt class="col-4">Metode Bayar</dt><dd class="col-8">{{ strtoupper($order->payment_method ?? '-') }}</dd>
              <dt class="col-4">Catatan</dt><dd class="col-8">{{ $order->catatan ?? '-' }}</dd>
            </dl>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header">Total & Pembayaran</div>
          <div class="card-body">
            @php
              $roomTotal = (int)($order->total_harga ?? 0);
              $cafeTotal = (int)($order->total_cafe ?? 0);
              $diskon    = (int)($order->diskon ?? 0);
              $biayaLain = (int)($order->biaya_tambahan ?? 0);
              $grand     = max(0, ($roomTotal + $cafeTotal) - $diskon + $biayaLain);
              $dp        = (int)($order->dp_amount ?? 0);
              $sisa      = max(0, $grand - $dp);
            @endphp
            <div class="d-flex justify-content-between">
              <div><strong>Kamar</strong></div>
              <div>Rp {{ number_format($roomTotal,0,',','.') }}</div>
            </div>
            <div class="d-flex justify-content-between">
              <div><strong>Cafe</strong></div>
              <div>Rp {{ number_format($cafeTotal,0,',','.') }}</div>
            </div>
            <div class="d-flex justify-content-between text-muted">
              <div>Diskon</div>
              <div>- Rp {{ number_format($diskon,0,',','.') }}</div>
            </div>
            <div class="d-flex justify-content-between text-muted">
              <div>Biaya Tambahan</div>
              <div>+ Rp {{ number_format($biayaLain,0,',','.') }}</div>
            </div>
            <hr/>
            <div class="d-flex justify-content-between">
              <div><strong>Grand Total</strong></div>
              <div><strong>Rp {{ number_format($grand,0,',','.') }}</strong></div>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <div>DP</div>
              <div>Rp {{ number_format($dp,0,',','.') }}</div>
            </div>
            <div class="d-flex justify-content-between">
              <div><strong>Sisa Pembayaran</strong></div>
              <div><strong>Rp {{ number_format($sisa,0,',','.') }}</strong></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card mb-3">
          <div class="card-header">Kamar</div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nomor</th>
                  <th>Tipe</th>
                  <th class="text-center">Malam</th>
                  <th class="text-end">Harga/mlm</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody>
              @foreach($order->items as $it)
                <tr>
                  <td>{{ $it->kamar->nomor_kamar ?? '-' }}</td>
                  <td>{{ $it->kamar->tipe ?? '-' }}</td>
                  <td class="text-center">{{ $it->malam }}</td>
                  <td class="text-end">{{ number_format($it->harga_per_malam,0,',','.') }}</td>
                  <td class="text-end">{{ number_format($it->subtotal,0,',','.') }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header">Pengaturan Tarif</div>
          <div class="card-body">
            <div class="row">
              <div class="col-6 mb-2"><strong>Diskon Review:</strong> {{ $order->discount_review ? 'Ya (-10%)' : 'Tidak' }}</div>
              <div class="col-6 mb-2"><strong>Diskon Follow:</strong> {{ $order->discount_follow ? 'Ya (-10%)' : 'Tidak' }}</div>
              <div class="col-6 mb-2"><strong>Tambahan Waktu:</strong> {{ $order->extra_time === 'half' ? '+1/2 Hari (50%)' : ($order->extra_time === 'sixth' ? '+1/6 Hari (35%)' : 'Tidak ada') }}</div>
              <div class="col-6 mb-2"><strong>Mode Per Kepala:</strong> {{ $order->per_head_mode ? 'Aktif' : 'Tidak' }}</div>
              @if(!is_null($order->diskon))
                <div class="col-12 mt-1 text-muted">Diskon tercatat: Rp {{ number_format($order->diskon,0,',','.') }}</div>
              @endif
            </div>
          </div>
        </div>

        @if($otherOrders->count())
        <div class="card mb-3">
          <div class="card-header">Riwayat Lain Pelanggan</div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Check-In</th>
                  <th>Check-Out</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              @foreach($otherOrders as $o)
                @php $m=[1=>'Dipesan',2=>'Check-In',3=>'Checkout',4=>'Dibatalkan']; @endphp
                <tr>
                  <td>#{{ $o->id }}</td>
                  <td>{{ \Carbon\Carbon::parse($o->tanggal_checkin)->format('d/m/Y') }}</td>
                  <td>{{ \Carbon\Carbon::parse($o->tanggal_checkout)->format('d/m/Y') }}</td>
                  <td>{{ $m[$o->status] ?? $o->status }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @endif
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-light" href="{{ route('booking.index') }}">Kembali</a>
      <a class="btn btn-warning text-white" href="{{ route('booking.edit', $order->id) }}">Edit Booking</a>
    </div>
  </div>
</div>
@endsection
