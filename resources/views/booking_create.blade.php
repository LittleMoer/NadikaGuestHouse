@extends('layouts.app_layout')

@section('booking')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Buat Booking Baru</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/dashboard">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('booking.index') }}">Booking</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Buat Booking</a>
                </li>
            </ul>
        </div>

        @if ($errors->hasBag('booking_create') && $errors->booking_create->any())
            <div class="alert alert-danger" style="margin-bottom:12px;">
                <ul style="margin:0;padding-left:18px;">
                    @foreach ($errors->booking_create->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Toasts: Success & Error -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 2000;">
            @if (session('success'))
            <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            @endif
            @if ($errors->hasBag('booking_create') && $errors->booking_create->any())
            <div class="toast text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Terjadi Kesalahan</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <ul class="mb-0" style="padding-left: 18px;">
                        @foreach ($errors->booking_create->all() as $err)
                            <li>{{ $err }}</li>
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

                // Prefill default booking window: today 12:00 -> tomorrow 12:00 if empty
                try {
                    const inpIn = document.querySelector('input[name="tanggal_checkin"][type="datetime-local"]');
                    const inpOut = document.querySelector('input[name="tanggal_checkout"][type="datetime-local"]');
                    if (inpIn && inpOut && (!inpIn.value || !inpOut.value)) {
                        const now = new Date();
                        const pad = n => (n<10? '0'+n : ''+n);
                        const setLocal = (d)=> `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                        // Normalize to 12:00 local
                        const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 12, 0, 0, 0);
                        const end = new Date(start.getTime() + 24*60*60*1000);
                        if (!inpIn.value) inpIn.value = setLocal(start);
                        if (!inpOut.value) inpOut.value = setLocal(end);
                    }
                } catch(e) { /* no-op */ }

                // Rupiah formatting for inputs with .rupiah
                function formatRupiah(val){
                    const num = (val||'').toString().replace(/[^0-9]/g,'');
                    if(!num) return '';
                    return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }
                document.querySelectorAll('input.rupiah').forEach(function(inp){
                    inp.addEventListener('input', function(){
                        const caret = this.selectionStart;
                        const raw = this.value.replace(/[^0-9]/g,'');
                        this.value = formatRupiah(raw);
                        // caret handling can be improved; keep at end for simplicity
                        this.setSelectionRange(this.value.length, this.value.length);
                    });
                    // initial format
                    inp.value = formatRupiah(inp.value);
                });
                // On submit, strip to numeric values
                document.querySelectorAll('form').forEach(function(f){
                    f.addEventListener('submit', function(){
                        this.querySelectorAll('input.rupiah').forEach(function(inp){
                            inp.value = (inp.value||'').toString().replace(/[^0-9]/g,'');
                        });
                    });
                });
            });
        </script>

        <form action="{{ route('booking.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Form Booking</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pelanggan</label>
                            <select name="pelanggan_id" class="form-control" required>
                                <optgroup label="Pelanggan">
                                    <option value="">-- Pilih Pelanggan --</option>
                                @foreach($pelangganList as $p)
                                    <option value="{{ $p->id }}" {{ old('pelanggan_id')==$p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                @endforeach
                                </optgroup>
                            </select>
                            <div style="margin-top:6px" class="d-flex justify-content mb-3 ">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPelanggan">
                                    Tambah Pelanggan
                                </button>
                            </div>
                            
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pilih Kamar (Multi)</label>
                            <select name="kamar_ids[]" class="form-control" multiple size="6" required>
                                @foreach($availableKamar as $k)
                                    <option value="{{ $k->id }}" {{ (collect(old('kamar_ids', request()->input('kamar_ids', []))))->contains($k->id) ? 'selected' : '' }}>
                                        {{ $k->nomor_kamar }} ({{ $k->tipe }}) - Rp{{ number_format($k->harga,0,',','.') }}/mlm
                                    </option>
                                @endforeach
                            </select>
                            <small style="font-size:.7rem;color:#555;">Tahan CTRL / SHIFT untuk memilih lebih dari satu.</small>
                            @error('kamar_ids','booking_create')
                                <div class="text-danger" style="font-size:.7rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check-In</label>
                            <input type="datetime-local" name="tanggal_checkin" value="{{ old('tanggal_checkin', request('tanggal_checkin')) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check-Out</label>
                            <input type="datetime-local" name="tanggal_checkout" value="{{ old('tanggal_checkout', request('tanggal_checkout')) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jumlah Tamu</label>
                            <input type="number" name="jumlah_tamu" min="1" class="form-control" value="{{ old('jumlah_tamu', request('jumlah_tamu',1)) }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Pemesanan</label>
                            @php $oldPem = old('pemesanan', request('pemesanan','0')); @endphp
                            <select name="pemesanan" class="form-control" required>
                                <option value="0" {{ $oldPem=='0' ? 'selected' : '' }}>Walk-In</option>
                                <option value="1" {{ $oldPem=='1' ? 'selected' : '' }}>Online (Traveloka)</option>
                                <option value="2" {{ $oldPem=='2' ? 'selected' : '' }}>Agent 1</option>
                                <option value="3" {{ $oldPem=='3' ? 'selected' : '' }}>Agent 2</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="1" {{ old('status')=='1' ? 'selected' : '' }}>Dipesan</option>
                                <option value="2" {{ old('status')=='2' ? 'selected' : '' }}>Check-In</option>
                                <option value="3" {{ old('status')=='3' ? 'selected' : '' }}>Check-Out</option>
                                <option value="4" {{ old('status')=='4' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">DP (Rp)</label>
                            <input type="text" name="dp_amount" class="form-control rupiah" value="{{ old('dp_amount', 0) }}" placeholder="Nominal DP" />
                            <small class="text-muted">Sisa akan dihitung otomatis.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Biaya Tambahan (Rp)</label>
                            <input type="text" name="biaya_tambahan" class="form-control rupiah" value="{{ old('biaya_tambahan', 0) }}" placeholder="Biaya lain-lain" />
                            <small class="text-muted">Opsional, akan ditambahkan ke grand total.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="payment_method" class="form-control">
                                <option value="">- Pilih -</option>
                                @php $pmOld = old('payment_method'); @endphp
                                <option value="cash" {{ $pmOld==='cash' ? 'selected' : '' }}>Cash</option>
                                <option value="transfer" {{ $pmOld==='transfer' ? 'selected' : '' }}>Transfer</option>
                                <option value="qris" {{ $pmOld==='qris' ? 'selected' : '' }}>QRIS</option>
                                <option value="card" {{ $pmOld==='card' ? 'selected' : '' }}>Kartu</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Diskon</label>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" name="discount_review" id="disc_review" value="1" {{ old('discount_review') ? 'checked' : '' }}>
                              <label class="form-check-label" for="disc_review">Review (10%)</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" name="discount_follow" id="disc_follow" value="1" {{ old('discount_follow') ? 'checked' : '' }}>
                              <label class="form-check-label" for="disc_follow">Follow Sosmed (10%)</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tambahan Waktu</label>
                            <select name="extra_time" class="form-control">
                                <option value="none" {{ old('extra_time','none')==='none' ? 'selected' : '' }}>Tidak ada</option>
                                <option value="h3" {{ old('extra_time')==='h3' ? 'selected' : '' }}>+3 jam (35%)</option>
                                <option value="h6" {{ old('extra_time')==='h6' ? 'selected' : '' }}>+6 jam (50%)</option>
                                <option value="h9" {{ old('extra_time')==='h9' ? 'selected' : '' }}>+9 jam (85%)</option>
                                <option value="d1" {{ old('extra_time')==='d1' ? 'selected' : '' }}>+1 hari (100%)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="per_head_mode" id="per_head_mode" value="1" {{ old('per_head_mode') ? 'checked' : '' }}>
                                <label class="form-check-label" for="per_head_mode">Mode per kepala (min 100k, >2 org +50k/org)</label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('booking.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Modal Tambah Pelanggan -->
        <div class="modal fade" id="modalPelanggan" tabindex="-1" aria-labelledby="modalPelangganLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPelangganLabel">Tambah Pelanggan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('penginap.create') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" name="nama" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="telepon" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <input type="text" name="alamat" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jenis Identitas</label>
                                <select name="jenis_identitas" class="form-control">
                                    <option value="">Pilih jenis</option>
                                    <option value="KTP">KTP</option>
                                    <option value="SIM">SIM</option>
                                    <option value="Kartu Pelajar">Kartu Pelajar</option>
                                    <option value="LAIN">Lain</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor Identitas</label>
                                <input type="text" name="nomor_identitas" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kewarganegaraan</label>
                                <input type="text" name="kewarganegaraan" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
