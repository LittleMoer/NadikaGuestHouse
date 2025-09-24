@extends('layouts.templateowner')

@section('booking')
        <div class="container">
          <div class="page-inner">
                        <div class="page-header">
                            <h4 class="page-title">Booking</h4>
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
                  <a href="#">Booking</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
              </ul>
            </div>
            @php
                $tanggalBooking = $tanggalBooking ?? date('Y-m-d');
                $statusStyles = [
                    'tersedia' => ['label' => 'Tersedia', 'badge' => 'bg-success', 'btn' => 'btn-success', 'icon' => 'text-success', 'ring' => 'border-success'],
                    'dipesan' => ['label' => 'Dipesan', 'badge' => 'bg-warning text-dark', 'btn' => 'btn-warning text-dark', 'icon' => 'text-warning', 'ring' => 'border-warning'],
                    'checkin' => ['label' => 'Check-In', 'badge' => 'bg-info text-dark', 'btn' => 'btn-info text-dark', 'icon' => 'text-info', 'ring' => 'border-info'],
                    'checkout' => ['label' => 'Checkout', 'badge' => 'bg-secondary', 'btn' => 'btn-secondary', 'icon' => 'text-secondary', 'ring' => 'border-secondary'],
                    'dibatalkan' => ['label' => 'Dibatalkan', 'badge' => 'bg-dark', 'btn' => 'btn-dark', 'icon' => 'text-dark', 'ring' => 'border-dark'],
                ];
            @endphp
            <div class="container py-4">
                <form method="GET" class="mb-3 flex gap-2 items-center flex-wrap">
                    <label for="tanggal" class="font-semibold">Tanggal Booking:</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggalBooking }}" class="border rounded px-2 py-1">
                    <button type="submit" class="btn btn-primary text-white px-4 py-2 rounded shadow hover:bg-blue-700">Tampilkan</button>
                                                    <button type="button" id="btnOpenBookingModal" class="btn btn-success ms-2">+ Booking</button>
                    <div class="ms-auto d-flex align-items-center gap-2" style="flex-wrap:wrap;">
                        <label class="mb-0" style="font-size:.85rem;font-weight:600;">Filter Status:</label>
                        <select id="filterStatus" class="form-select form-select-sm" style="min-width:150px;">
                            <option value="">Semua</option>
                            <option value="Tersedia">Tersedia</option>
                            <option value="Dipesan">Dipesan</option>
                            <option value="Check-In">Check-In</option>
                            <option value="Checkout">Checkout</option>
                            <option value="Dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                </form>
                                            @if (session('success'))
                                                <div class="alert alert-success py-2 px-3">{{ session('success') }}</div>
                                            @endif
                                            @if (session('error'))
                                                <div class="alert alert-danger py-2 px-3">{{ session('error') }}</div>
                                            @endif
                <div class="table-responsive">
                    <table id="tabel-booking" class="min-w-full border rounded-lg shadow bg-white">
                        <thead class="bg-blue-200">
                            <tr>
                                <th class="border px-3 py-2">Nomor</th>
                                <th class="border px-3 py-2">Tipe</th>
                                <th class="border px-3 py-2">Kapasitas</th>
                                <th class="border px-3 py-2">Status</th>
                                <th class="border px-3 py-2">Tamu / Jadwal Aktif</th>
                                <th class="border px-3 py-2">Check-In</th>
                                <th class="border px-3 py-2">Check-Out</th>
                                <th class="border px-3 py-2">Metode</th>
                                <th class="border px-3 py-2">Total (Rp)</th>
                                <th class="border px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $flatten = collect($groupedKamar)->flatten(1); @endphp
                        @forelse($flatten as $wrapper)
                            @php
                                $room = $wrapper['room'];
                                $booking = $wrapper['activeBooking'];
                                $statusMap = [1=>'dipesan',2=>'checkin',3=>'checkout',4=>'dibatalkan'];
                                $statusKey = $statusMap[$booking->status] ?? 'tersedia';
                                $labelMap = [
                                  'tersedia'=>'Tersedia','dipesan'=>'Dipesan','checkin'=>'Check-In','checkout'=>'Checkout','dibatalkan'=>'Dibatalkan'
                                ];
                                $badgeColor = [
                                  'tersedia'=>'bg-success','dipesan'=>'bg-warning text-dark','checkin'=>'bg-info text-dark','checkout'=>'bg-secondary','dibatalkan'=>'bg-dark'
                                ][$statusKey] ?? 'bg-light';
                            @endphp
                            <tr data-row-status="{{ $labelMap[$statusKey] }}" @if($booking) data-booking-id="{{ $booking->id }}" data-booking-status="{{ $labelMap[$statusKey] }}" data-booking-nama="{{ $booking->pelanggan?->nama ?? 'Tamu' }}" data-booking-checkin="{{ \Carbon\Carbon::parse($booking->tanggal_checkin)->format('d/m/Y H:i') }}" data-booking-checkout="{{ \Carbon\Carbon::parse($booking->tanggal_checkout)->format('d/m/Y H:i') }}" data-booking-metode="{{ $booking->pemesanan==0?'Walk-In':'Online' }}" data-booking-total="{{ number_format($booking->total_harga,0,',','.') }}" @endif>
                                <td class="border px-3 py-2">{{ $room->nomor_kamar }}</td>
                                <td class="border px-3 py-2">{{ $room->tipe }}</td>
                                <td class="border px-3 py-2 text-center">{{ $room->kapasitas }}</td>
                                <td class="border px-3 py-2"><span class="badge {{ $badgeColor }}">{{ $labelMap[$statusKey] }}</span></td>
                                <td class="border px-3 py-2" style="min-width:160px;">
                                    @if($booking)
                                        <strong>{{ $booking->pelanggan?->nama ?? 'Tamu' }}</strong><br>
                                        <span class="text-muted" style="font-size:.75rem;">{{ \Carbon\Carbon::parse($booking->tanggal_checkin)->format('d M H:i') }} - {{ \Carbon\Carbon::parse($booking->tanggal_checkout)->format('d M H:i') }}</span>
                                    @else
                                        <em style="color:#999;font-size:.8rem;">(kosong)</em>
                                    @endif
                                </td>
                                <td class="border px-3 py-2">{{ $booking ? \Carbon\Carbon::parse($booking->tanggal_checkin)->format('d/m/Y H:i') : '-' }}</td>
                                <td class="border px-3 py-2">{{ $booking ? \Carbon\Carbon::parse($booking->tanggal_checkout)->format('d/m/Y H:i') : '-' }}</td>
                                <td class="border px-3 py-2">{{ $booking ? ($booking->pemesanan==0?'Walk-In':'Online') : '-' }}</td>
                                <td class="border px-3 py-2 text-end">{{ $booking ? number_format($booking->total_harga,0,',','.') : '-' }}</td>
                                <td class="border px-3 py-2" style="min-width:140px;">
                                    @if(!$booking)
                                        <button data-room="{{ $room->id }}" class="btn btn-sm btn-success btn-open-create">Booking</button>
                                    @else
                                        @if($statusKey==='dipesan')
                                            <form class="form-status-action d-inline" data-action-type="checkin" action="{{ route('booking.status', $booking->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="action" value="checkin">
                                                <button class="btn btn-sm btn-info">Check-In</button>
                                            </form>
                                            <form class="form-status-action d-inline" data-action-type="cancel" action="{{ route('booking.status', $booking->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="action" value="cancel">
                                                <button class="btn btn-sm btn-danger">Batal</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-secondary btn-detail">Detail</button>
                                        @elseif($statusKey==='checkin')
                                            <form class="form-status-action d-inline" data-action-type="checkout" action="{{ route('booking.status', $booking->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="action" value="checkout">
                                                <button class="btn btn-sm btn-success">Checkout</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-secondary btn-detail">Detail</button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-detail">Detail</button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-3">Tidak ada data kamar.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
                <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                <style>
                    .badge {font-size:.65rem;letter-spacing:.5px;}
                    #bookingDetailModal .modal-body dl {margin:0;display:grid;grid-template-columns:110px 1fr;row-gap:6px;column-gap:8px;font-size:.8rem;}
                    #bookingDetailModal .modal-body dt {font-weight:600;color:#555;}
                    #bookingDetailModal .modal-body dd {margin:0;}
                </style>
                <!-- Detail Modal -->
                <div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header py-2">
                                <h6 class="modal-title">Detail Booking</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <dl>
                                    <dt>ID</dt><dd id="bd_id">-</dd>
                                    <dt>Status</dt><dd id="bd_status">-</dd>
                                    <dt>Tamu</dt><dd id="bd_nama">-</dd>
                                    <dt>Check-In</dt><dd id="bd_checkin">-</dd>
                                    <dt>Check-Out</dt><dd id="bd_checkout">-</dd>
                                    <dt>Metode</dt><dd id="bd_metode">-</dd>
                                    <dt>Total</dt><dd id="bd_total">-</dd>
                                </dl>
                            </div>
                            <div class="modal-footer py-2 d-flex justify-content-between">
                                <div id="bd_actions" style="display:flex;gap:6px;"></div>
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        const tbl = $('#tabel-booking').DataTable({
                            paging:false,
                            searching:true,
                            info:false,
                            lengthChange:false,
                            order:[[0,'asc']],
                            language:{search:'Cari:'}
                        });
                        // Filter status
                        const statusFilter = document.getElementById('filterStatus');
                        statusFilter && statusFilter.addEventListener('change', function(){
                            const val = this.value;
                            tbl.column(3).search(val ? '^'+val+'$' : '', true, false).draw();
                        });
                        // Buka modal create dengan pre-select kamar
                        document.querySelectorAll('.btn-open-create').forEach(btn=>{
                            btn.addEventListener('click', function(){
                                const roomId = this.getAttribute('data-room');
                                const selectKamar = document.querySelector('#modalCreateBooking select[name="kamar_id"]');
                                if (selectKamar) selectKamar.value = roomId;
                                const openBtn = document.getElementById('btnOpenBookingModal');
                                if(openBtn){ openBtn.click(); }
                            });
                        });
                        // Detail modal
                        const detailModalEl = document.getElementById('bookingDetailModal');
                        let bootstrapModal = null; try { bootstrapModal = new bootstrap.Modal(detailModalEl); } catch(e){}
                        function openDetail(row){ if(bootstrapModal) bootstrapModal.show(); }
                        function fillDetail(tr){
                            const id = tr.dataset.bookingId;
                            document.getElementById('bd_id').textContent = id || '-';
                            document.getElementById('bd_status').textContent = tr.dataset.bookingStatus || '-';
                            document.getElementById('bd_nama').textContent = tr.dataset.bookingNama || '-';
                            document.getElementById('bd_checkin').textContent = tr.dataset.bookingCheckin || '-';
                            document.getElementById('bd_checkout').textContent = tr.dataset.bookingCheckout || '-';
                            document.getElementById('bd_metode').textContent = tr.dataset.bookingMetode || '-';
                            document.getElementById('bd_total').textContent = tr.dataset.bookingTotal || '-';
                            const actionsDiv = document.getElementById('bd_actions');
                            actionsDiv.innerHTML='';
                            const status = tr.dataset.bookingStatus;
                            if(status==='Dipesan') addAction('Check-In','checkin','btn-info');
                            if(status==='Dipesan') addAction('Batal','cancel','btn-danger');
                            if(status==='Check-In') addAction('Checkout','checkout','btn-success');
                            function addAction(label, action, cls){
                                const f = document.createElement('form');
                                f.className='form-status-action'; f.method='POST'; f.action='{{ url('/booking/status') }}/'+id; f.style.display='inline';
                                f.innerHTML=`@csrf<input type="hidden" name="action" value="${action}"><button type="submit" class="btn btn-sm ${cls}">${label}</button>`;
                                actionsDiv.appendChild(f);
                            }
                        }
                        document.querySelectorAll('#tabel-booking tbody tr').forEach(tr=>{
                            tr.querySelectorAll('.btn-detail').forEach(btn=>{
                                btn.addEventListener('click', function(){ fillDetail(tr); openDetail(tr); });
                            });
                        });
                        // AJAX status update
                        function updateRowDisplay(tr, data){
                            if(!data || !data.booking) return;
                            tr.dataset.bookingStatus = data.booking.status_label;
                            const statusCell = tr.querySelector('td:nth-child(4) span.badge');
                            if(statusCell){ statusCell.textContent = data.booking.status_label; statusCell.className='badge '+data.booking.badge_class; }
                            // Replace actions cell
                            const actionsCell = tr.querySelector('td:last-child');
                            if(actionsCell){
                                let html='';
                                if(data.booking.status_key==='dipesan'){
                                    html += actionButtonHtml('checkin','Check-In','btn-info');
                                    html += actionButtonHtml('cancel','Batal','btn-danger');
                                    html += detailBtnHtml();
                                } else if(data.booking.status_key==='checkin'){
                                    html += actionButtonHtml('checkout','Checkout','btn-success');
                                    html += detailBtnHtml();
                                } else {
                                    html += detailBtnHtml();
                                }
                                actionsCell.innerHTML = html;
                                actionsCell.querySelectorAll('.form-status-action').forEach(bindAjaxForm);
                                actionsCell.querySelectorAll('.btn-detail').forEach(b=> b.addEventListener('click', ()=>{ fillDetail(tr); openDetail(tr); }));
                            }
                            function actionButtonHtml(action,label,cls){
                                return `<form class="form-status-action d-inline" data-action-type="${action}" action="${data.booking.status_url}" method="POST" style="display:inline;">@csrf<input type="hidden" name="action" value="${action}"><button class="btn btn-sm ${cls}">${label}</button></form>`;
                            }
                            function detailBtnHtml(){ return '<button type="button" class="btn btn-sm btn-outline-secondary btn-detail">Detail</button>'; }
                        }
                        function bindAjaxForm(form){
                            form.addEventListener('submit', function(e){
                                e.preventDefault();
                                const fd = new FormData(form);
                                fetch(form.getAttribute('action'), {
                                    method:'POST',
                                    headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
                                    body:fd
                                })
                                .then(r=> r.json())
                                .then(j=>{
                                    if(j.success){
                                        const tr = form.closest('tr');
                                        updateRowDisplay(tr, j);
                                        if(document.getElementById('bookingDetailModal').classList.contains('show')){
                                            fillDetail(tr);
                                        }
                                    } else { alert(j.message||'Gagal'); }
                                })
                                .catch(()=> alert('Gagal memproses aksi'));
                            });
                        }
                        document.querySelectorAll('.form-status-action').forEach(bindAjaxForm);
                    });
                </script>
            </div>
                        <!-- Booking Create Modal -->
                        <div id="modalCreateBooking" class="modal-overlay" aria-hidden="true">
                            <div class="modal-card" style="max-width:640px;">
                                <button type="button" class="modal-close" id="closeModalCreateBooking" aria-label="Tutup">&times;</button>
                                <h3 style="margin-top:0;">Buat Booking</h3>
                                @if ($errors->hasBag('booking_create') && $errors->booking_create->any())
                                    <div class="alert alert-danger" style="margin-bottom:12px;">
                                        <ul style="margin:0;padding-left:18px;">
                                            @foreach ($errors->booking_create->all() as $err)
                                                <li>{{ $err }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form action="{{ route('booking.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Pelanggan</label>
                                            <select name="pelanggan_id" class="form-control" required>
                                                <option value="">-- Pilih Pelanggan --</option>
                                                @foreach($pelangganList as $p)
                                                    <option value="{{ $p->id }}" {{ old('pelanggan_id')==$p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Kamar</label>
                                            <select name="kamar_id" class="form-control" required>
                                                <option value="">-- Pilih Kamar --</option>
                                                @foreach($availableKamar as $k)
                                                    <option value="{{ $k->id }}" {{ old('kamar_id')==$k->id ? 'selected' : '' }}>{{ $k->nomor_kamar }} ({{ $k->tipe }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Check-In</label>
                                            <input type="datetime-local" name="tanggal_checkin" value="{{ old('tanggal_checkin') }}" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Check-Out</label>
                                            <input type="datetime-local" name="tanggal_checkout" value="{{ old('tanggal_checkout') }}" class="form-control" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Jumlah Tamu</label>
                                            <input type="number" name="jumlah_tamu" min="1" class="form-control" value="{{ old('jumlah_tamu',1) }}" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Jenis Pemesanan</label>
                                            <select name="pemesanan" class="form-control" required>
                                                <option value="0" {{ old('pemesanan')==='0' ? 'selected' : '' }}>Walk-In</option>
                                                <option value="1" {{ old('pemesanan')==='1' ? 'selected' : '' }}>Online</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Catatan</label>
                                            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
                                        </div>
                                    </div>
                                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                                        <button type="button" class="btn btn-light" id="batalModalCreateBooking">Batal</button>
                                        <button type="submit" class="btn btn-success">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <style>
                            .modal-overlay { position: fixed; inset:0; z-index:1055; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.45); padding:16px; opacity:0; visibility:hidden; transition:opacity .2s ease, visibility 0s linear .2s; }
                            .modal-overlay.show { opacity:1; visibility:visible; transition:opacity .2s ease; }
                            .modal-card { width:100%; background:#fff; border-radius:14px; box-shadow:0 18px 36px rgba(0,0,0,.25); padding:22px; position:relative; transform:translateY(14px) scale(.97); opacity:.95; transition:transform .25s ease, opacity .2s ease; }
                            .modal-overlay.show .modal-card { transform:translateY(0) scale(1); opacity:1; }
                            .modal-close { position:absolute; top:8px; right:12px; border:0; background:transparent; font-size:30px; line-height:1; cursor:pointer; color:#999; }
                            .modal-close:hover { color:#e74c3c; }
                        </style>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                const modal = document.getElementById('modalCreateBooking');
                                const openBtn = document.getElementById('btnOpenBookingModal');
                                const closeBtn = document.getElementById('closeModalCreateBooking');
                                const cancelBtn= document.getElementById('batalModalCreateBooking');
                                function openM(){ modal.classList.add('show'); modal.setAttribute('aria-hidden','false'); }
                                function closeM(){ modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); }
                                openBtn && openBtn.addEventListener('click', openM);
                                closeBtn && closeBtn.addEventListener('click', closeM);
                                cancelBtn && cancelBtn.addEventListener('click', closeM);
                                modal && modal.addEventListener('click', e => { if(e.target===modal) closeM(); });
                                document.addEventListener('keydown', e => { if(e.key==='Escape') closeM(); });
                                @if ($errors->hasBag('booking_create') && $errors->booking_create->any())
                                    openM();
                                @endif
                            });
                        </script>
          </div>
        </div>
@endsection