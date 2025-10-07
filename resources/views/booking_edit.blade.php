@extends('layouts.app_layout')

@section('booking')
<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <h4 class="page-title">Edit Booking #{{ $order->id }}</h4>
      <ul class="breadcrumbs">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="{{ route('booking.index') }}">Booking</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Edit</a></li>
      </ul>
    </div>

    <!-- Toasts: Success & Error -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 2000;">
      @if (session('success'))
      <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
        <div class="d-flex">
          <div class="toast-body">{{ session('success') }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
      @endif
      @if ($errors->any())
      <div class="toast text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
        <div class="toast-header bg-danger text-white">
          <strong class="me-auto">Terjadi Kesalahan</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          <ul class="mb-0" style="padding-left: 18px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      </div>
      @endif
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        const toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.forEach(function(toastEl){
          const t = new bootstrap.Toast(toastEl);
          t.show();
        });

        // Rupiah formatting
        function formatRupiah(val){
          const num = (val||'').toString().replace(/[^0-9]/g,'');
          if(!num) return '';
          return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        document.querySelectorAll('input.rupiah').forEach(function(inp){
          inp.addEventListener('input', function(){
            const raw = this.value.replace(/[^0-9]/g,'');
            this.value = formatRupiah(raw);
            this.setSelectionRange(this.value.length, this.value.length);
          });
          inp.value = formatRupiah(inp.value);
        });
        document.querySelectorAll('form').forEach(function(f){
          f.addEventListener('submit', function(){
            this.querySelectorAll('input.rupiah').forEach(function(inp){
              inp.value = (inp.value||'').toString().replace(/[^0-9]/g,'');
            });
          });
        });
      });
    </script>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Form Edit Booking</span>
        <a class="btn btn-light btn-sm" href="{{ route('booking.detail', $order->id) }}">Lihat Detail</a>
      </div>
      <form action="{{ route('booking.update', $order->id) }}" method="POST">
        @csrf
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Pelanggan</label>
              <select name="pelanggan_id" class="form-control">
                <option value="">-- Pilih Pelanggan --</option>
                @foreach($pelangganList as $p)
                  <option value="{{ $p->id }}" {{ (old('pelanggan_id', $order->pelanggan_id)==$p->id) ? 'selected' : '' }}>{{ $p->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Jumlah Tamu</label>
              <input type="number" min="1" name="jumlah_tamu_total" class="form-control" value="{{ old('jumlah_tamu_total', $order->jumlah_tamu_total) }}" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Check-In</label>
              <input type="datetime-local" name="tanggal_checkin" class="form-control" value="{{ old('tanggal_checkin', \Carbon\Carbon::parse($order->tanggal_checkin)->format('Y-m-d\TH:i')) }}" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Check-Out</label>
              <input type="datetime-local" name="tanggal_checkout" class="form-control" value="{{ old('tanggal_checkout', \Carbon\Carbon::parse($order->tanggal_checkout)->format('Y-m-d\TH:i')) }}" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Jenis Pemesanan</label>
              <select name="pemesanan" class="form-control" required>
                <option value=0 {{ old('pemesanan', $order->pemesanan)==='0' || old('pemesanan', $order->pemesanan)==0 ? 'selected' : '' }}>Walk-In</option>
                <option value=1 {{ old('pemesanan', $order->pemesanan)==='1' || old('pemesanan', $order->pemesanan)==1 ? 'selected' : '' }}>Online (Traveloka)</option>
                <option value=2 {{ old('pemesanan', $order->pemesanan)==='2' || old('pemesanan', $order->pemesanan)==2 ? 'selected' : '' }}>Agent 1</option>
                <option value=3 {{ old('pemesanan', $order->pemesanan)==='3' || old('pemesanan', $order->pemesanan)==3 ? 'selected' : '' }}>Agent 2</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-control">
                <option value=1 {{ (string)old('status',$order->status)==='1' ? 'selected' : '' }}>Dipesan</option>
                <option value=2 {{ (string)old('status',$order->status)==='2' ? 'selected' : '' }}>Check-In</option>
                <option value=3 {{ (string)old('status',$order->status)==='3' ? 'selected' : '' }}>Check-Out</option>
                <option value=4 {{ (string)old('status',$order->status)==='4' ? 'selected' : '' }}>Dibatalkan</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Status Pembayaran</label>
              <select name="payment_status" class="form-control">
                <option value="dp" {{ old('payment_status',$order->payment_status)==='dp' ? 'selected' : '' }}>DP</option>
                <option value="lunas" {{ old('payment_status',$order->payment_status)==='lunas' ? 'selected' : '' }}>Lunas</option>
                <option value="dp_cancel" {{ old('payment_status',$order->payment_status)==='dp_cancel' ? 'selected' : '' }}>DP Batal</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">DP (Rp)</label>
              <input type="text" name="dp_amount" class="form-control rupiah" value="{{ old('dp_amount', $order->dp_amount) }}" />
              <small class="text-muted">Sisa akan dihitung otomatis saat detail.</small>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Biaya Tambahan (Rp)</label>
              <input type="text" name="biaya_tambahan" class="form-control rupiah" value="{{ old('biaya_tambahan', $order->biaya_tambahan) }}" />
              <small class="text-muted">Opsional, akan ditambahkan ke grand total.</small>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Metode Pembayaran</label>
              @php $pmOld = old('payment_method', $order->payment_method); @endphp
              <select name="payment_method" class="form-control">
                <option value="">- Pilih -</option>
                <option value="cash" {{ $pmOld==='cash' ? 'selected' : '' }}>Cash</option>
                <option value="transfer" {{ $pmOld==='transfer' ? 'selected' : '' }}>Transfer</option>
                <option value="qris" {{ $pmOld==='qris' ? 'selected' : '' }}>QRIS</option>
                <option value="card" {{ $pmOld==='card' ? 'selected' : '' }}>Kartu</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Diskon</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="discount_review" id="disc_review" value="1" {{ old('discount_review',$order->discount_review) ? 'checked' : '' }}>
                <label class="form-check-label" for="disc_review">Review (10%)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="discount_follow" id="disc_follow" value="1" {{ old('discount_follow',$order->discount_follow) ? 'checked' : '' }}>
                <label class="form-check-label" for="disc_follow">Follow Sosmed (10%)</label>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tambahan Waktu</label>
              <select name="extra_time" class="form-control">
                <option value="none" {{ old('extra_time',$order->extra_time)==='none' ? 'selected' : '' }}>Tidak ada</option>
                <option value="half" {{ old('extra_time',$order->extra_time)==='half' ? 'selected' : '' }}>+1/2 Hari (+50%)</option>
                <option value="sixth" {{ old('extra_time',$order->extra_time)==='sixth' ? 'selected' : '' }}>+1/6 Hari (+35%)</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="per_head_mode" id="per_head_mode" value="1" {{ old('per_head_mode',$order->per_head_mode) ? 'checked' : '' }}>
                <label class="form-check-label" for="per_head_mode">Mode per kepala (min 100k, >2 org +50k/org)</label>
              </div>
            </div>
            <div class="col-12 mb-3">
              <label class="form-label">Catatan</label>
              <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $order->catatan) }}</textarea>
            </div>
          </div>
          <small class="text-muted">Catatan: perubahan set kamar dan harga per malam dikerjakan dari halaman detail/harga khusus bila diperlukan.</small>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
          <a href="{{ route('booking.detail', $order->id) }}" class="btn btn-light">Batal</a>
          <button type="submit" class="btn btn-success">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
