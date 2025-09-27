@extends('layouts.templateowner')
@section('booking')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Booking</h4>
            <ul class="breadcrumbs">
                <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Booking</a></li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div class="fw-semibold">Daftar Booking</div>
                <button type="button" class="btn btn-success btn-sm" id="btnOpenBookingModal">+ Booking</button>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success py-2 px-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3">{{ session('error') }}</div>
        @endif

        <!-- Orders Table -->
        <div class="table-responsive mb-4">
            <table id="tabel-booking" class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Pelanggan</th>
                        <th>Kamar (Jumlah)</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Metode</th>
                        <th class="text-end">Kamar (Rp)</th>
                        <th class="text-end">Cafe (Rp)</th>
                        <th class="text-end">Grand Total (Rp)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    @php $meta = $order->status_meta; $channelLabelMap=['walkin'=>'Walk-In','agent1'=>'Agent 1','agent2'=>'Agent 2','traveloka'=>'Traveloka','cancel'=>'-']; $channelLabel=$channelLabelMap[$meta['channel']] ?? ucfirst($meta['channel']); @endphp
                    <tr data-booking-id="{{ $order->id }}" data-show-url="{{ route('booking.show',$order->id) }}" data-payment="{{ $meta['payment'] }}" data-channel="{{ $meta['channel'] }}">
                        <td style="font-weight:700;color:{{ $meta['payment']==='dp'?'#faed00':'#fff' }};text-shadow:{{ $meta['payment']==='lunas'?'0 0 3px rgba(0,0,0,.6)':'none' }}">#{{ $order->id }}</td>
                        <td>
                            <span class="badge" style="background:{{ $meta['background'] }};color:{{ $meta['text_color'] }};min-width:90px;display:inline-block;">{{ $meta['label'] }}</span>
                        </td>
                        <td>{{ $order->pelanggan?->nama ?? '-' }}</td>
                        <td style="min-width:180px;">
                            @php $rooms=$order->items->map(fn($it)=> $it->kamar?->nomor_kamar)->filter()->values(); @endphp
                            <span style="font-size:.75rem;">{{ $rooms->join(', ') }}</span>
                            <div><small class="text-muted">{{ $rooms->count() }} kamar</small></div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($order->tanggal_checkin)->format('d/m/Y H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->tanggal_checkout)->format('d/m/Y H:i') }}</td>
                        <td>{{ $channelLabel }}</td>
                        <td class="text-end">{{ number_format($order->total_harga,0,',','.') }}</td>
                        <td class="text-end">{{ number_format($order->total_cafe ?? 0,0,',','.') }}</td>
                        <td class="text-end">{{ number_format(($order->total_harga)+($order->total_cafe ?? 0),0,',','.') }}</td>
                        <td style="min-width:110px;">
                            <button type="button" class="btn btn-sm btn-secondary btn-detail">Detail</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-3">Tidak ada booking untuk tanggal ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mb-4">
            {{ $orders->links() }}
        </div>

        <!-- Detail Modal (Enhanced unified) -->
        
        <style>
            .badge{font-size:.65rem;letter-spacing:.5px;}
            #bookingDetailModal .modal-body dl{margin:0;display:grid;grid-template-columns:120px 1fr;row-gap:6px;column-gap:10px;font-size:.8rem;}
            #bookingDetailModal .modal-body dt{font-weight:600;color:#444;}
            #bookingDetailModal .modal-body dd{margin:0;}
            #bd_items_body tr:hover{background:#fafafa;}
            #bd_other_orders_body tr:hover{background:#f5f5f5;}
            #bd_other_orders_body tr.history-active{background:#d9edf7 !important;}
        </style>
        <div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">Detail Booking</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="bd_loading" style="display:none;font-size:.75rem;color:#888;">Memuat...</div>
                        <dl id="bd_maininfo">
                            <dt>ID</dt><dd id="bd_id">-</dd>
                            <dt>Status</dt><dd id="bd_status">-</dd>
                            <dt>Pelanggan</dt><dd id="bd_nama">-</dd>
                            <dt>Telepon</dt><dd id="bd_telepon">-</dd>
                            <dt>Jumlah Tamu total</dt><dd id="bd_jumlah_tamu_total">-</dd>
                            <dt>Check-In</dt><dd id="bd_checkin">-</dd>
                            <dt>Check-Out</dt><dd id="bd_checkout">-</dd>
                            <dt>Metode</dt><dd id="bd_metode">-</dd>
                                <dt>Total Kamar</dt><dd id="bd_total">-</dd>
                            <dt>Total Cafe</dt><dd id="bd_total_cafe">-</dd>
                            <dt>Grand Total</dt><dd id="bd_grand_total">-</dd>
                                <dt>Pembayaran</dt><dd id="bd_payment"><span id="bd_payment_badge" class="badge bg-warning text-dark">DP</span></dd>
                            <dt>Catatan</dt><dd id="bd_catatan">-</dd>
                        </dl>
                        <div id="bd_summary" class="mt-2" style="font-size:.7rem;color:#555;display:none;">
                            <strong id="bd_summary_text"></strong>
                        </div>
                        <div class="mt-3">
                            <h6 class="mb-1" style="font-size:.75rem;font-weight:700;letter-spacing:.5px;">ITEM KAMAR</h6>
                            <div class="table-responsive" style="max-height:180px;overflow:auto;border:1px solid #eee;">
                                <table class="table table-sm mb-0" style="font-size:.7rem;">
                                    <thead class="table-light"><tr><th>Kamar</th><th>Tipe</th><th class="text-center">Malam</th><th class="text-end">Harga/Mlm</th><th class="text-end">Subtotal</th></tr></thead>
                                    <tbody id="bd_items_body"><tr><td colspan="5" class="text-center">-</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3" id="bd_other_orders_wrap" style="display:none;">
                            <h6 class="mb-1" style="font-size:.75rem;font-weight:700;letter-spacing:.5px;">RIWAYAT LAIN (10 Terbaru)</h6>
                            <div class="table-responsive" style="max-height:140px;overflow:auto;border:1px solid #eee;">
                                <table class="table table-sm mb-0" style="font-size:.65rem;">
                                    <thead class="table-light"><tr><th>ID</th><th>Check-In</th><th>Check-Out</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                                    <tbody id="bd_other_orders_body"><tr><td colspan="5" class="text-center">-</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                        <hr class="my-2">
                        <form id="formEditOrder" style="display:none;">
                            @csrf
                            <input type="hidden" name="booking_id" id="edit_booking_id">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Pelanggan</label>
                                    <select name="pelanggan_id" id="edit_pelanggan_id" class="form-select form-select-sm">
                                        <option value="">-- Pilih --</option>
                                        @foreach($pelangganList as $p)
                                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Check-In</label>
                                    <input type="datetime-local" name="tanggal_checkin" id="edit_checkin" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Check-Out</label>
                                    <input type="datetime-local" name="tanggal_checkout" id="edit_checkout" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Metode</label>
                                    <select name="pemesanan" id="edit_pemesanan" class="form-select form-select-sm" required>
                                        <option value="0">Walk-In</option>
                                        <option value="1">Online</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Status</label>
                                    <select name="status" id="edit_status" class="form-select form-select-sm">
                                        <option value="1">Dipesan</option>
                                        <option value="2">Check-In</option>
                                        <option value="3">Check-Out</option>
                                        <option value="4">Dibatalkan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">DP (%)</label>
                                    <input type="number" min="0" max="100" step="1" name="dp_percentage" id="edit_dp_percentage" class="form-control form-control-sm" placeholder="0-100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Status Pembayaran</label>
                                    <select name="payment_status" id="edit_payment_status" class="form-select form-select-sm">
                                        <option value="dp">DP</option>
                                        <option value="lunas">Lunas</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Jumlah Tamu</label>
                                    <input type="number" min="1" name="jumlah_tamu_total" id="edit_jumlah_tamu_total" class="form-control form-control-sm">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" style="font-size:.7rem;font-weight:600;">Catatan</label>
                                    <textarea name="catatan" id="edit_catatan" rows="2" class="form-control form-control-sm"></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-2">
                                <button type="button" id="btnCancelEdit" class="btn btn-light btn-sm">Batal</button>
                                <button type="submit" class="btn btn-success btn-sm">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer py-2 d-flex justify-content-between">
                        <div id="bd_actions" style="display:flex;gap:6px;"></div>
                        <div class="d-flex gap-2">
                            <form id="formDeleteBooking" method="POST" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button" id="btnDeleteBooking" class="btn btn-outline-danger btn-sm" style="display:none;">Hapus</button>
                            <button type="button" id="btnToggleEdit" class="btn btn-warning btn-sm">Edit</button>
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Scripts (Refactored) -->
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            // ================== UTIL ==================
            const FMT = new Intl.NumberFormat('id-ID');
            const qs = sel=> document.querySelector(sel);
            const qsa = sel=> Array.from(document.querySelectorAll(sel));
            const modalEl = document.getElementById('bookingDetailModal');
            let modalInstance = null;
            try{ if(window.bootstrap?.Modal){ modalInstance = new bootstrap.Modal(modalEl); } }catch(e){ console.warn('Bootstrap modal missing:', e); }
            const EL = {
                id: qs('#bd_id'), status: qs('#bd_status'), nama: qs('#bd_nama'), telepon: qs('#bd_telepon'),
                checkin: qs('#bd_checkin'), checkout: qs('#bd_checkout'), metode: qs('#bd_metode'),
                total: qs('#bd_total'), totalCafe: qs('#bd_total_cafe'), grandTotal: qs('#bd_grand_total'),
                catatan: qs('#bd_catatan'), summaryWrap: qs('#bd_summary'), summaryText: qs('#bd_summary_text'),
                itemsBody: qs('#bd_items_body'), otherWrap: qs('#bd_other_orders_wrap'), otherBody: qs('#bd_other_orders_body'),
                editForm: qs('#formEditOrder'), editId: qs('#edit_booking_id'), editCheckin: qs('#edit_checkin'),
                editCheckout: qs('#edit_checkout'), editPemesanan: qs('#edit_pemesanan'), editCatatan: qs('#edit_catatan'), editPelanggan: qs('#edit_pelanggan_id'), editJumlahTamu: qs('#edit_jumlah_tamu_total'),
                actions: qs('#bd_actions'), payBadge: qs('#bd_payment_badge'), btnToggleEdit: qs('#btnToggleEdit'), btnCancelEdit: qs('#btnCancelEdit')
            };
            const loader = document.getElementById('bd_loading');
            const mainInfo = document.getElementById('bd_maininfo');
            function openModal(){ if(modalInstance) modalInstance.show(); else { modalEl.classList.add('show'); modalEl.style.display='block'; } }
            function setLoading(b){ if(!loader||!mainInfo) return; loader.style.display=b?'block':'none'; mainInfo.style.opacity=b?'.35':'1'; }
            function fmt(n){ return FMT.format(n||0); }
            function dtLocal(str){ if(!str) return ''; const d=new Date(str); const p=n=> n.toString().padStart(2,'0'); return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`; }
            function safe(el,val){ if(el) el.textContent = (val===null||val===undefined||val==='')?'-':val; }
            function updatePaymentVisual(row){ if(!row) return; const idCell=row.querySelector('td:first-child'); if(!idCell) return; const pay=row.getAttribute('data-payment'); idCell.style.color= pay==='dp'? '#faed00':'#ffffff'; idCell.style.fontWeight='700'; idCell.style.textShadow= pay==='lunas' ? '0 0 3px rgba(0,0,0,.6)' : 'none'; }

            // ================== FETCH DETAIL ==================
            async function fetchDetail(tr){
                const id = tr.getAttribute('data-booking-id');
                const url = tr.getAttribute('data-show-url') || `{{ url('/booking') }}/${id}`;
                setLoading(true);
                try{
                    const r = await fetch(url, {headers:{'Accept':'application/json'}});
                    if(!r.ok){ throw new Error('HTTP '+r.status); }
                    const ct = r.headers.get('content-type')||''; if(!ct.includes('application/json')){ const t=await r.text(); console.error('Non JSON:', t.substring(0,300)); throw new Error('Respon bukan JSON'); }
                    return await r.json();
                } finally { setLoading(false); }
            }

            // ================== RENDERERS ==================
            function renderItems(items){
                if(!EL.itemsBody) return;
                if(!Array.isArray(items) || !items.length){ EL.itemsBody.innerHTML='<tr><td colspan="5" class="text-center">-</td></tr>'; return; }
                EL.itemsBody.innerHTML = items.map(it=>`<tr data-item-id='${it.id}'>
                    <td>${it.nomor_kamar||'-'}</td>
                    <td>${it.tipe||'-'}</td>
                    <td class='text-center'>${it.malam}</td>
                    <td class='text-end'><input type='number' min='0' class='form-control form-control-sm inp-harga' style='width:90px;' value='${it.harga_per_malam}'></td>
                    <td class='text-end'><input type='number' min='0' class='form-control form-control-sm inp-subtotal' style='width:110px;' value='${it.subtotal}'></td>
                </tr>`).join('');
            }
            function renderHistory(list){
                if(!EL.otherWrap||!EL.otherBody) return;
                if(!Array.isArray(list) || !list.length){ EL.otherWrap.style.display='none'; return; }
                EL.otherWrap.style.display='block';
                EL.otherBody.innerHTML = list.map(o=>`<tr data-other-id='${o.id}' style='cursor:pointer;'>
                    <td>#${o.id}</td><td>${new Date(o.tanggal_checkin).toLocaleDateString()}</td><td>${new Date(o.tanggal_checkout).toLocaleDateString()}</td><td>${o.status_label}</td><td class='text-end'>${fmt(o.total_harga)}</td>
                </tr>`).join('');
            }
                function renderActions(data){
                if(!EL.actions) return; EL.actions.innerHTML='';
                // Quick helpers: toggle dp<->lunas, cancel/restore via lifecycle status
                const meta = data.status_meta || {};
                function btn(label, variant, handler){
                    const b=document.createElement('button'); b.type='button'; b.className='btn btn-sm '+variant; b.textContent=label; b.addEventListener('click', handler); EL.actions.appendChild(b);
                }
                // Toggle payment only
                btn(meta.payment==='lunas'?'Set DP':'Set Lunas', meta.payment==='lunas'?'btn-warning':'btn-success', ()=> quickTogglePayment(data.id, meta.payment==='lunas'?'dp':'lunas'));
                // Print Nota (totals only)
                btn('Print Nota', 'btn-primary', ()=> { window.open(`{{ url('/booking') }}/${data.id}/nota`, '_blank'); });
            }
            function renderBasic(data){
                safe(EL.id, data.id); safe(EL.status, data.status_label); safe(EL.nama, data.pelanggan?.nama||'-'); safe(EL.telepon, data.pelanggan?.telepon||'-');
                safe(EL.checkin, new Date(data.tanggal_checkin).toLocaleString()); safe(EL.checkout, new Date(data.tanggal_checkout).toLocaleString());
                safe(EL.metode, data.status_meta?.channel==='walkin'?'Walk-In': (data.status_meta?.channel||'-'));
                safe(EL.total, fmt(data.total_harga)); safe(EL.totalCafe, fmt(data.total_cafe)); safe(EL.grandTotal, fmt(data.grand_total)); safe(EL.catatan, data.catatan||'-');
                    // Bind jumlah tamu to any present element (support both ids if existed)
                    const elJmlTot = document.getElementById('bd_jumlah_tamu_total'); if(elJmlTot){ elJmlTot.textContent = data.jumlah_tamu_total ?? '-'; }
                    const elJml = document.getElementById('bd_jumlah_tamu'); if(elJml){ elJml.textContent = data.jumlah_tamu_total ?? '-'; }
                if(EL.payBadge){
                    const pay = data.status_meta?.payment || 'dp';
                    EL.payBadge.textContent = (pay==='dp'?'DP':'LUNAS') + (data.dp_percentage?` (${data.dp_percentage}%)`: '');
                    EL.payBadge.className='badge ' + (pay==='lunas'?'bg-success':'bg-warning text-dark');
                }
                if(EL.summaryWrap && EL.summaryText){ EL.summaryWrap.style.display='block'; EL.summaryText.textContent=`${data.total_kamar} kamar x ${data.total_malam} malam`; }
                if(EL.editId){
                    EL.editId.value=data.id;
                    EL.editCheckin.value=dtLocal(data.tanggal_checkin);
                    EL.editCheckout.value=dtLocal(data.tanggal_checkout);
                    // pemesanan legacy kept: guess 0 for walkin
                    if(EL.editPemesanan){ EL.editPemesanan.value = (data.status_meta?.channel==='walkin'? 0:1); }
                    EL.editCatatan.value=data.catatan||'';
                    if(EL.editPelanggan && data.pelanggan?.id){ EL.editPelanggan.value=data.pelanggan.id; }
                    if(EL.editJumlahTamu){ EL.editJumlahTamu.value=data.jumlah_tamu_total || ''; }
                    if(document.getElementById('edit_status')){ document.getElementById('edit_status').value=data.status; }
                    if(document.getElementById('edit_dp_percentage')){ document.getElementById('edit_dp_percentage').value=data.dp_percentage ?? ''; }
                    if(document.getElementById('edit_payment_status')){ document.getElementById('edit_payment_status').value=data.payment_status || 'dp'; }
                }
                // show delete button when not already deleted and not checked-out (optional rule: allow delete any except checkout)
                const btnDel = document.getElementById('btnDeleteBooking');
                const formDel = document.getElementById('formDeleteBooking');
                if(btnDel && formDel){ formDel.action='{{ url('/booking') }}/'+data.id; btnDel.style.display='inline-block'; }
            }

            // ================== FILL DETAIL ==================
            async function showDetail(tr){
                openModal();
                try{ const data = await fetchDetail(tr); renderBasic(data); renderItems(data.items); renderHistory(data.other_orders); renderActions(data); attachDynamicHandlers(data, tr); }
                catch(e){ console.error(e); alert('Gagal memuat detail: '+e.message); }
            }

            // ================== HANDLERS ==================
            function attachDynamicHandlers(data, tableRow){
                // Unified action buttons already have handlers at creation.
            }

            // Quick toggle payment only
            function quickTogglePayment(id, newPayment){
                const fd = new FormData();
                // minimal required fields for update: reuse existing edit fields values if present
                fd.append('tanggal_checkin', EL.editCheckin.value || new Date().toISOString());
                fd.append('tanggal_checkout', EL.editCheckout.value || new Date(Date.now()+86400000).toISOString());
                fd.append('pemesanan', EL.editPemesanan?.value || 0);
                if(EL.editPelanggan?.value) fd.append('pelanggan_id', EL.editPelanggan.value);
                if(EL.editJumlahTamu?.value) fd.append('jumlah_tamu_total', EL.editJumlahTamu.value);
                fd.append('payment_status', newPayment);
                if(document.getElementById('edit_dp_percentage')?.value){ fd.append('dp_percentage', document.getElementById('edit_dp_percentage').value); }
                fetch(`{{ url('/booking') }}/${id}/update`,{
                        method:'POST',
                        headers:{
                            'Accept':'application/json',
                            'X-Requested-With':'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                        },
                        body:fd
                    })
                    .then(r=>r.json()).then(j=>{ 
                        if(!j.success){ alert(j.message||'Gagal ubah status'); return; } 
                        const row=document.querySelector(`#tabel-booking tbody tr[data-booking-id='${id}']`); 
                        if(row){ 
                            row.setAttribute('data-payment', newPayment);
                            updatePaymentVisual(row);
                            showDetail(row); 
                        } 
                    })
                    .catch(()=> alert('Gagal koneksi status'));
            }
            function quickUpdateLifecycle(id, newStatus){
                const fd = new FormData();
                fd.append('tanggal_checkin', EL.editCheckin.value || new Date().toISOString());
                fd.append('tanggal_checkout', EL.editCheckout.value || new Date(Date.now()+86400000).toISOString());
                fd.append('pemesanan', EL.editPemesanan?.value || 0);
                if(EL.editPelanggan?.value) fd.append('pelanggan_id', EL.editPelanggan.value);
                if(EL.editJumlahTamu?.value) fd.append('jumlah_tamu_total', EL.editJumlahTamu.value);
                fd.append('status', newStatus);
                if(document.getElementById('edit_payment_status')?.value){ fd.append('payment_status', document.getElementById('edit_payment_status').value); }
                if(document.getElementById('edit_dp_percentage')?.value){ fd.append('dp_percentage', document.getElementById('edit_dp_percentage').value); }
                fetch(`{{ url('/booking') }}/${id}/update`,{
                        method:'POST',
                        headers:{
                            'Accept':'application/json',
                            'X-Requested-With':'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                        },
                        body:fd
                    })
                    .then(r=>r.json()).then(j=>{ if(!j.success){ alert(j.message||'Gagal ubah status'); return; } const row=document.querySelector(`#tabel-booking tbody tr[data-booking-id='${id}']`); if(row){ showDetail(row); } })
                    .catch(()=> alert('Gagal koneksi status'));
            }

            function refreshRowFromStatus(row, bookingJson){
                if(!row||!bookingJson) return; row.dataset.bookingStatus=bookingJson.status_label;
                const badge=row.querySelector('td:nth-child(2) span.badge'); if(badge){ badge.textContent=bookingJson.status_label; badge.className='badge bg-secondary'; }
                const cell = row.querySelector('td:last-child'); if(cell){ cell.innerHTML='<button type="button" class="btn btn-sm btn-secondary btn-detail">Detail</button>'; }
            }
            function statusBtn(action,label,cls){ return `<form class="form-status-action d-inline" action="${window.location.origin}/booking/${EL.id.textContent}/status" method="POST" style="display:inline;">@csrf<input type="hidden" name="action" value="${action}"><button class="btn btn-sm ${cls}">${label}</button></form>`; }

            // ================== PRICE UPDATE ==================
            function collectItemPayload(){
                const rows = Array.from(EL.itemsBody.querySelectorAll('tr[data-item-id]'));
                return rows.map(r=>{
                    const id = r.getAttribute('data-item-id');
                    const vHarga = (r.querySelector('.inp-harga')?.value ?? '').toString().trim();
                    const vSub = (r.querySelector('.inp-subtotal')?.value ?? '').toString().trim();
                    const harga = vHarga === '' ? null : Number.parseInt(vHarga, 10);
                    const subtotal = vSub === '' ? null : Number.parseInt(vSub, 10);
                    return {
                        id: id,
                        harga_per_malam: Number.isNaN(harga) ? null : harga,
                        subtotal: Number.isNaN(subtotal) ? null : subtotal
                    };
                });
            }
            // Remove separate price buttons; saving will be unified via main form submit

            // ================== EDIT FORM ==================
            EL.btnToggleEdit?.addEventListener('click',()=>{
                if(!EL.editForm) return; const show = EL.editForm.style.display==='none' || !EL.editForm.style.display; EL.editForm.style.display= show? 'block':'none'; EL.btnToggleEdit.textContent= show? 'Tutup Edit':'Edit';
            });
            EL.btnCancelEdit?.addEventListener('click',()=>{ if(!EL.editForm) return; EL.editForm.style.display='none'; EL.btnToggleEdit.textContent='Edit'; });
            EL.editForm?.addEventListener('submit', async function(e){
                e.preventDefault();
                const id = EL.editId.value;
                const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
                try{
                    // 1) Save item prices first
                    const itemsPayload = { items: collectItemPayload() };
                    // Filter payload: keep only entries where one of the numeric fields is a valid integer
                    itemsPayload.items = itemsPayload.items.filter(it => (it.harga_per_malam !== null && Number.isInteger(it.harga_per_malam)) || (it.subtotal !== null && Number.isInteger(it.subtotal)));
                    let r1 = await fetch(`{{ url('/booking') }}/${id}/prices`, {
                        method:'POST',
                        headers:{'Content-Type':'application/json','Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
                        body: JSON.stringify(itemsPayload)
                    });
                    let j1 = await r1.json();
                    if(!j1.success){ alert(j1.message||'Gagal menyimpan harga'); return; }
                    // Update totals in table (kamar total and grand total)
                    const row = document.querySelector(`#tabel-booking tbody tr[data-booking-id='${id}']`);
                    if(row){
                        row.querySelector('td:nth-child(8)').textContent = fmt(j1.order.total_harga);
                        const cafeVal = parseInt((row.querySelector('td:nth-child(9)')?.textContent||'0').replace(/[^0-9]/g,''))||0;
                        row.querySelector('td:nth-child(10)').textContent = fmt(j1.order.total_harga + cafeVal);
                    }
                    // 2) Save order fields
                    const fd = new FormData(EL.editForm);
                    let r2 = await fetch(`{{ url('/booking') }}/${id}/update`, {
                        method:'POST',
                        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
                        body: fd
                    });
                    let j2 = await r2.json();
                    if(!j2.success){ alert(j2.message||'Gagal menyimpan perubahan'); return; }
                    // Done: close edit and refresh modal content
                    EL.editForm.style.display='none';
                    EL.btnToggleEdit.textContent='Edit';
                    if(row){
                        row.querySelector('td:nth-child(5)').textContent = new Date(fd.get('tanggal_checkin')).toLocaleString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
                        row.querySelector('td:nth-child(6)').textContent = new Date(fd.get('tanggal_checkout')).toLocaleString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
                        showDetail(row);
                    }
                }catch(err){
                    alert('Gagal menyimpan');
                }
            });

            // Delete booking
            document.getElementById('btnDeleteBooking')?.addEventListener('click', function(){
                const id = EL.editId.value;
                if(!id) return;
                if(!confirm('Hapus booking ini beserta item & cafe orders?')) return;
                const formDel = document.getElementById('formDeleteBooking');
                const fd = new FormData(formDel);
                fetch(formDel.action, {method:'POST', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body: fd})
                    .then(r=> r.json())
                    .then(j=> { if(j.success){ const row=document.querySelector(`#tabel-booking tbody tr[data-booking-id='${id}']`); if(row){ row.remove(); } if(window.bootstrap?.Modal){ const inst=bootstrap.Modal.getInstance(modalEl); inst?.hide(); } else { modalEl.style.display='none'; } } else { alert(j.message||'Gagal hapus'); } })
                    .catch(()=> alert('Gagal koneksi hapus'));
            });

            // ================== TABLE & FILTER ==================
            // DataTables & Filter removed; using server-side pagination

            // Legacy status forms removed; actions via modal only

            // Row detail delegation
            const tbody = document.querySelector('#tabel-booking tbody');
            tbody?.addEventListener('click', function(e){ const btn=e.target.closest('.btn-detail'); if(!btn) return; const tr=btn.closest('tr'); if(!tr) return; showDetail(tr); });

            // Initial payment visuals
            qsa('#tabel-booking tbody tr').forEach(updatePaymentVisual);
        });
        </script>

        <!-- Create Booking Modal -->
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
                            <label class="form-label">Pilih Kamar (Multi)</label>
                            <select name="kamar_ids[]" class="form-control" multiple size="6" required>
                                @foreach($availableKamar as $k)
                                    <option value="{{ $k->id }}" {{ (collect(old('kamar_ids')))->contains($k->id) ? 'selected' : '' }}>
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
                            <label class="form-label">DP (%)</label>
                            <input type="number" name="dp_percentage" min="0" max="100" step="1" class="form-control" value="{{ old('dp_percentage') }}" placeholder="0-100" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status Pembayaran</label>
                            <select name="payment_status" class="form-control">
                                <option value="dp" {{ old('payment_status','dp')==='dp' ? 'selected' : '' }}>DP</option>
                                <option value="lunas" {{ old('payment_status')==='lunas' ? 'selected' : '' }}>Lunas</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" id="batalModalCreateBooking">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        <style>
            .modal-overlay{position:fixed;inset:0;z-index:1055;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);padding:16px;opacity:0;visibility:hidden;transition:opacity .2s ease,visibility 0s linear .2s;}
            .modal-overlay.show{opacity:1;visibility:visible;transition:opacity .2s ease;}
            .modal-card{width:100%;background:#fff;border-radius:14px;box-shadow:0 18px 36px rgba(0,0,0,.25);padding:22px;position:relative;transform:translateY(14px) scale(.97);opacity:.95;transition:transform .25s ease,opacity .2s ease;}
            .modal-overlay.show .modal-card{transform:translateY(0) scale(1);opacity:1;}
            .modal-close{position:absolute;top:8px;right:12px;border:0;background:transparent;font-size:30px;line-height:1;cursor:pointer;color:#999;}
            .modal-close:hover{color:#e74c3c;}
        </style>
        <script>
            document.addEventListener('DOMContentLoaded',function(){
                const modal=document.getElementById('modalCreateBooking');
                const openBtn=document.getElementById('btnOpenBookingModal');
                const closeBtn=document.getElementById('closeModalCreateBooking');
                const cancelBtn=document.getElementById('batalModalCreateBooking');
                function openM(){ modal.classList.add('show'); modal.setAttribute('aria-hidden','false'); }
                function closeM(){ modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); }
                openBtn?.addEventListener('click',openM); closeBtn?.addEventListener('click',closeM); cancelBtn?.addEventListener('click',closeM); modal?.addEventListener('click',e=>{ if(e.target===modal) closeM(); }); document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeM(); });
                @if ($errors->hasBag('booking_create') && $errors->booking_create->any()) openM(); @endif
            });
        </script>
    </div>
</div>
@endsection