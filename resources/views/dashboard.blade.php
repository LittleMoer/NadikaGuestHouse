@extends('layouts.templateowner')

@section('dashboard')
    <div class="container">
      <div class="page-inner">
        <div class="page-header">
          <h1 class="h1 dashboard-title">Dashboard</h1>
        </div>
        <div class="page-subtitle">Selamat datang {{ Auth::user()->name }}</div>
        <style>
          /* Dashboard Table Cleanup */
          .dashboard-title {font-size:2rem;font-weight:700;margin:0;}
          .page-subtitle {font-size:1.05rem;font-weight:500;margin-bottom:1rem;color:#444;}
          .nav-period {display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;margin:1.25rem 0 1rem;}
          .month-switch {display:flex;align-items:center;gap:.5rem;}
          .btn-period {padding:.5rem 1rem;border-radius:.55rem;background:#0d6efd;color:#fff;font-size:1.05rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;line-height:1;}
          .btn-period:hover {background:#0953bb;color:#fff;text-decoration:none;}
          .select-period select {border-radius:.55rem;padding:.45rem .9rem;font-size:.95rem;min-width:110px;border:1px solid #d0d7de;background:#fff;}
          .select-period button {border:none;cursor:pointer;}
          .select-period button.btn-period {font-size:.95rem;font-weight:600;}
          .dash-table-wrapper {overflow-x:auto;background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;box-shadow:0 2px 6px #0000000d;padding:1rem;}
          table.dash-table {border-collapse:separate;border-spacing:0;min-width:100%;border:1px solid #d0d7de;border-radius:.65rem;overflow:hidden;}
          table.dash-table thead th {background:#ff9d25;color:#1f2328;font-weight:700;font-size:.82rem;padding:.55rem .6rem;text-align:center;vertical-align:middle;white-space:nowrap;}
          table.dash-table thead th.group-header {background:#ff9d25;color:#1f2328;font-size:.78rem;border-bottom:0;}
          table.dash-table thead tr.second-header th {background:#ffc44d;color:#333;font-weight:600;font-size:.75rem;border-top:1px solid #e5e7eb;}
          th.corner-fill {background:#ff9d25;width:88px;border-bottom:0;}
          th.tanggal-head {background:#ffc44d;font-weight:600;width:88px;}
          th.total-col-head {background:#0d6efd;color:#fff;font-size:.8rem;}
          table.dash-table tbody td {padding:.45rem .5rem;font-size:.78rem;text-align:center;border:1px solid #ececec;}
          table.dash-table th:first-child, table.dash-table td:first-child {position:sticky;left:0;background:#fafafa;z-index:2;}
          table.dash-table thead th:first-child {z-index:3;}
          .day-label {display:block;font-weight:600;font-size:.8rem;color:#111;}
          .day-name {display:block;font-size:.65rem;color:#6a737d;margin-top:.15rem;}
          .weekend {background:#edf6ff !important;}
          .status-cell {font-weight:700;font-size:1rem;line-height:1.1;padding:.35rem .25rem;min-width:32px;}
          .status-kosong {background:#ffffff;}
          .status-dipesan {background:#fff5b8;} /* softer pale yellow distinct from header */
          .status-ditempati {background:#ff6b6b;color:#fff;}
          .status-ditempati svg, .status-ditempati span {color:#fff;}
          .total-col {background:#0d6efd;color:#fff;font-weight:700;}
          .total-row td {background:#ffd657;font-weight:700;font-size:.85rem;color:#222;}
          .legend {display:flex;flex-wrap:wrap;gap:.6rem;margin:.25rem 0 1rem;align-items:center;font-size:.7rem;}
          .legend-item {display:flex;align-items:center;gap:.3rem;}
          .legend-swatch {width:18px;height:14px;border-radius:3px;border:1px solid #c3c6d1;box-shadow:0 1px 2px #0001;}
          .swatch-kosong {background:#fff;}
          .swatch-dipesan {background:#ffe9a6;}
            .swatch-ditempati {background:#ff6b6b;}
          .sticky-header thead th {position:sticky;top:0;z-index:5;}
          /* Hover focus */
          tbody tr:hover td.status-cell {outline:2px solid #333; outline-offset:-2px;}
          /* Responsive tweak */
          @media (max-width:900px){
            .status-cell {min-width:40px;font-size:.9rem;}
            table.dash-table tbody td {padding:.4rem .35rem;}
          }
        </style>
        {{-- DEMO BLOCK COMMENTED OUT. Data kini berasal dari DashboardController --}}
        {{--
            Blok sebelumnya membuat data dummy kamar & booking.
            Dipertahankan dalam komentar jika sewaktu-waktu diperlukan untuk referensi.
        --}}
        <div class="nav-period">
            <div class="month-switch">
                <a href="?bulan={{ $prevMonth }}&tahun={{ $prevYear }}" class="btn-period" title="Bulan Sebelumnya">&#8592;</a>
                <span class="fw-bold" style="font-size:1.05rem;color:#222;font-weight:600;">{{ DateTime::createFromFormat('!m', $bulan)->format('F') }} {{ $tahun }}</span>
                <a href="?bulan={{ $nextMonth }}&tahun={{ $nextYear }}" class="btn-period" title="Bulan Berikutnya">&#8594;</a>
            </div>
            <form method="GET" class="select-period" style="display:flex;gap:.5rem;align-items:center;">
                <select name="bulan">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == $bulan ? 'selected' : '' }}>{{ DateTime::createFromFormat('!m', $i)->format('F') }}</option>
                    @endfor
                </select>
                <select name="tahun">
                    @for ($y = \Carbon\Carbon::now()->year - 2; $y <= \Carbon\Carbon::now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn-period">Tampilkan</button>
            </form>
        </div>

                <div class="legend">
                    <div class="legend-item"><span class="legend-swatch swatch-kosong" style="background:#fff;"></span> Kosong</div>
                    <div class="legend-item"><span class="legend-swatch" style="background:#dc3545;"></span> Walk-In (Channel)</div>
                    <div class="legend-item"><span class="legend-swatch" style="background:#6f42c1;"></span> Agent 1</div>
                    <div class="legend-item"><span class="legend-swatch" style="background:#198754;"></span> Agent 2</div>
                    <div class="legend-item"><span class="legend-swatch" style="background:#0d6efd;"></span> Traveloka</div>
                    <div class="legend-item"><span class="legend-swatch" style="background:#555;"></span> Dibatalkan</div>
                      <div class="legend-item"><span class="legend-swatch" style="background:#faed00;"></span> Tulisan Kuning = DP</div>
                    <div class="legend-item"><span class="legend-swatch" style="background:#ffffff;border:1px solid #ccc;"></span> Tulisan Putih + Glow = Lunas</div>
                </div>

        <div class="dash-table-wrapper">
            <table class="dash-table sticky-header">
                <thead>
                    @php $loopJenis = isset($orderedJenisKamar) ? $orderedJenisKamar : $jenisKamar; @endphp
                    <tr class="first-header">
                        <th class="group-header">Jenis Kamar</th>
                        @foreach ($loopJenis as $jenis)
                            @php $jumlahJenis = isset($kamarGrouped) ? ($kamarGrouped[$jenis]->count() ?? 0) : collect($kamarList)->where('tipe', $jenis)->count(); @endphp
                            <th class="group-header" colspan="{{ $jumlahJenis }}">{{ $jenis }}</th>
                        @endforeach
                        <th class="total-col-head" rowspan="2">Total Terisi</th>
                    </tr>
                    <tr class="second-header" >
                        <th class="tanggal-head" >Tanggal</th>
                        @foreach ($loopJenis as $jenis)
                            @foreach (($kamarGrouped[$jenis] ?? collect()) as $kamar)
                                <th>{{ $kamar->nomor_kamar }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tanggalList as $tanggal)
                        @php 
                            $carbonDate = \Carbon\Carbon::parse($tanggal);
                            $isWeekend = $carbonDate->isWeekend();
                            $dayName = $carbonDate->translatedFormat('l');
                            $totalTerisi = 0;
                        @endphp
                        <tr class="day-row {{ $isWeekend ? 'weekend' : '' }}">
                            <td>
                                <span class="day-label">{{ $carbonDate->format('d M') }}</span>
                                <span class="day-name">{{ $dayName }}</span>
                            </td>
                            @foreach ($loopJenis as $jenis)
                                @foreach (($kamarGrouped[$jenis] ?? collect()) as $kamar)
                                    @php
                                        $cell = $statusBooking[$tanggal][$kamar->id] ?? ['status'=>'kosong','booking_id'=>null];
                                        $status = $cell['status'];
                                        $bookingId = $cell['booking_id'];
                                        if ($status === 'ditempati') $totalTerisi++;
                                        $display = $bookingId ? $bookingId : '';
                                        $bg = $cell['background'] ?? null; $txt = $cell['text_color'] ?? null;
                                        $style = '';
                                        if($bookingId && $bg){ $style .= 'background:'.$bg.';'; }
                                        if($bookingId && $txt){ $style .= 'color:'.$txt.';'; }
                                        if($bookingId && ($cell['payment'] ?? '')==='lunas'){ $style .= 'text-shadow:0 0 3px rgba(0,0,0,.55);'; }
                                        // differentiate booked (dp) vs occupied maybe border
                                        if($status==='dipesan'){ $style .= 'border:2px solid #fff8d1;'; }
                                    @endphp
                                    <td class="status-cell status-{{ $status }} dash-booking-cell" 
                                        data-tanggal="{{ $tanggal }}" 
                                        data-kamar-id="{{ $kamar->id }}" 
                                        data-status="{{ $status }}" 
                                        data-booking-id="{{ $bookingId ?? '' }}" 
                                        data-channel="{{ $cell['channel'] ?? '' }}"
                                        data-payment="{{ $cell['payment'] ?? '' }}"
                                        style="{{ $style }}"
                                        title="{{ $bookingId ? ('Booking ID: '.$bookingId) : 'Klik untuk buat booking' }}">
                                        {{ $display }}
                                    </td>
                                @endforeach
                            @endforeach
                            <td class="status-cell total-col">{{ $totalTerisi }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="{{ (isset($kamarList)?count($kamarList):0) + 1 }}">Total Kamar Terisi Bulan Ini</td>
                        <td>{{ $totalKamarTerisiBulan }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
    </div>
        {{-- Modals for quick booking creation from dashboard --}}
        <style>
            .dash-modal-overlay {position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1600;opacity:0;visibility:hidden;transition:.25s;}
            .dash-modal-overlay.show {opacity:1;visibility:visible;}
            .dash-modal {background:#fff;border-radius:14px;padding:20px 22px;width:100%;max-width:560px;box-shadow:0 18px 38px -12px rgba(0,0,0,.35);position:relative;transform:translateY(18px);opacity:.9;transition:.35s;}
            .dash-modal-overlay.show .dash-modal {transform:translateY(0);opacity:1;}
            .dash-modal h3 {margin:0 0 12px;font-size:1.15rem;font-weight:600;}
            .dash-close {position:absolute;top:8px;right:10px;border:none;background:transparent;font-size:26px;cursor:pointer;line-height:1;color:#999;}
            .dash-close:hover {color:#e74c3c;}
            .flex-row {display:flex;gap:.75rem;flex-wrap:wrap;}
            .form-group {margin-bottom:12px;width:100%;}
            .form-group.half {flex:1 1 240px;}
            .form-group label {font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#444;margin-bottom:4px;display:block;}
            .form-group input, .form-group select, .form-group textarea {width:100%;border:1px solid #d0d7de;border-radius:8px;padding:8px 10px;font-size:.85rem;background:#fff;}
            .actions {display:flex;justify-content:flex-end;gap:.6rem;margin-top:4px;}
            .btn-sm {padding:.55rem 1rem;border-radius:8px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;}
            .btn-neutral {background:#f1f2f4;color:#222;}
            .btn-neutral:hover {background:#e2e4e8;}
            .btn-primary2 {background:#0d6efd;color:#fff;}
            .btn-primary2:hover {background:#0a56c3;}
            .btn-accent {background:#16a34a;color:#fff;}
            .btn-accent:hover {background:#13833c;}
            .divider {height:1px;background:#e5e7eb;margin:10px 0;}
            .selectable-row {cursor:pointer;padding:6px 8px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:6px;}
            .selectable-row:hover {background:#f1f5f9;}
            .selectable-row.active {background:#0d6efd;color:#fff;border-color:#0d6efd;}
            .mini-badge {background:#0d6efd;color:#fff;font-size:.65rem;padding:2px 6px;border-radius:12px;margin-left:6px;text-transform:uppercase;letter-spacing:.5px;}
            .scroll-area {max-height:240px;overflow:auto;padding-right:4px;}
            .error-text {color:#dc2626;font-size:.7rem;margin-top:2px;}
        </style>
        <div id="modalSelectPelanggan" class="dash-modal-overlay" aria-hidden="true">
            <div class="dash-modal">
                <button class="dash-close" data-close>&times;</button>
                <h3>Pilih / Tambah Pelanggan</h3>
                <div class="flex-row">
                    <div class="form-group" style="flex:1 1 100%;">
                        <input type="text" id="filterPelanggan" placeholder="Cari nama / telepon" />
                    </div>
                </div>
                <div class="scroll-area" id="pelangganListContainer">
                    @php $pelangganAll = \App\Models\Pelanggan::orderBy('nama')->limit(200)->get(); @endphp
                    @forelse($pelangganAll as $pl)
                        <div class="selectable-row" data-id="{{ $pl->id }}" data-nama="{{ $pl->nama }}" data-telepon="{{ $pl->telepon }}">
                            <strong>{{ $pl->nama }}</strong> <span style="color:#666;font-size:.7rem;">{{ $pl->telepon }}</span>
                        </div>
                    @empty
                        <div style="font-size:.8rem;color:#777;">Belum ada data pelanggan.</div>
                    @endforelse
                </div>
                <div class="divider"></div>
                        <details style="margin-bottom:8px;">
                            <summary style="cursor:pointer;font-weight:600;font-size:.8rem;">Tambah Pelanggan Baru</summary>
                            <form id="formCreatePelanggan" style="margin-top:8px;">
                                @csrf
                                <div class="flex-row" style="gap:.85rem;">
                                    <div class="form-group half">
                                        <label>Nama</label>
                                        <input type="text" name="nama" required />
                                    </div>
                                    <div class="form-group half">
                                        <label>Telepon</label>
                                        <input type="text" name="telepon" required />
                                    </div>
                                    <div class="form-group half">
                                        <label>Email</label>
                                        <input type="email" name="email" />
                                    </div>
                                    <div class="form-group half">
                                        <label>Tempat Lahir</label>
                                        <input type="text" name="tempat_lahir" />
                                    </div>
                                    <div class="form-group half">
                                        <label>Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir" />
                                    </div>
                                    <div class="form-group half">
                                        <label>Kewarganegaraan</label>
                                        <input type="text" name="kewarganegaraan" />
                                    </div>
                                    <div class="form-group half">
                                        <label>Jenis Identitas</label>
                                        <select name="jenis_identitas" id="fp_jenis_identitas">
                                            <option value="">-- Pilih --</option>
                                            <option value="KTP">KTP</option>
                                            <option value="SIM">SIM</option>
                                            <option value="PASPOR">Paspor</option>
                                            <option value="LAIN">Lainnya</option>
                                        </select>
                                    </div>
                                    <div class="form-group half d-none" id="wrap_jenis_identitas_lain">
                                        <label>Isi Jenis Lain</label>
                                        <input type="text" name="jenis_identitas_lain" />
                                    </div>
                                    <div class="form-group half">
                                        <label>Nomor Identitas</label>
                                        <input type="text" name="nomor_identitas" />
                                    </div>
                                    <div class="form-group" style="flex:1 1 100%;">
                                        <label>Alamat</label>
                                        <textarea name="alamat" rows="2" required></textarea>
                                    </div>
                                    <div class="form-group" style="flex:1 1 100%;margin-top:-4px;">
                                        <small style="font-size:.65rem;color:#666;display:block;">Kolom opsional boleh dikosongkan jika tidak tersedia.</small>
                                    </div>
                                </div>
                                <div class="actions">
                                    <button type="submit" class="btn-sm btn-accent">Simpan Pelanggan</button>
                                </div>
                            </form>
                        </details>
                <div class="actions">
                    <button class="btn-sm btn-neutral" data-close>Batal</button>
                    <button id="btnLanjutBooking" class="btn-sm btn-primary2" disabled>Lanjut Booking</button>
                </div>
            </div>
        </div>
        <div id="modalCreateBooking" class="dash-modal-overlay" aria-hidden="true">
            <div class="dash-modal">
                <button class="dash-close" data-close>&times;</button>
                <h3>Buat Booking</h3>
                <form id="formQuickBooking" method="POST" action="{{ route('booking.store') }}">
                    @csrf
                    <input type="hidden" name="pelanggan_id" id="qb_pelanggan_id" />
                    <div class="form-group" style="flex:1 1 100%;">
                        <label>Pilih Kamar (Multi)</label>
                        <select name="kamar_ids[]" id="qb_kamar_ids" multiple size="6" required style="width:100%;border:1px solid #d0d7de;border-radius:8px;padding:6px 8px;font-size:.8rem;">
                            @php $allKamarForQuick = \App\Models\Kamar::orderBy('tipe')->orderBy('nomor_kamar')->get(); @endphp
                            @foreach($allKamarForQuick as $km)
                                <option value="{{ $km->id }}">{{ $km->nomor_kamar }} ({{ $km->tipe }}) - Rp{{ number_format($km->harga,0,',','.') }}</option>
                            @endforeach
                        </select>
                        <small style="font-size:.6rem;color:#555;">Gunakan CTRL / SHIFT untuk memilih beberapa kamar.</small>
                    </div>
                    <div class="flex-row">
                        <div class="form-group half">
                            <label>Tanggal & Waktu Check-in</label>
                            <input type="datetime-local" name="tanggal_checkin" id="qb_checkin" required />
                        </div>
                        <div class="form-group half">
                            <label>Tanggal & Waktu Check-out</label>
                            <input type="datetime-local" name="tanggal_checkout" id="qb_checkout" required />
                        </div>
                        <div class="form-group half">
                            <label>Jumlah Tamu</label>
                            <input type="number" name="jumlah_tamu" value="1" min="1" required />
                        </div>
                        <div class="form-group half">
                            <label>Metode</label>
                            <select name="pemesanan" required>
                                <option value="0">Walk-in</option>
                                <option value="1">Online</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Status</label>
                            <select name="status">
                                <option value="1">Dipesan</option>
                                <option value="2">Check-In</option>
                                <option value="3">Check-Out</option>
                                <option value="4">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Status Pembayaran</label>
                            <select name="payment_status">
                                <option value="dp" selected>DP</option>
                                <option value="lunas">Lunas</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>DP (%)</label>
                            <input type="number" name="dp_percentage" min="0" max="100" step="1" placeholder="0-100" />
                        </div>
                        <div class="form-group" style="flex:1 1 100%;">
                            <label>Catatan</label>
                            <textarea name="catatan" rows="2"></textarea>
                        </div>
                    </div>
                    <div style="font-size:.7rem;color:#666;margin-top:-4px;">Checkout minimal +1 hari dari check-in.</div>
                    <div class="actions">
                        <button type="button" class="btn-sm btn-neutral" data-close>Batal</button>
                        <button type="submit" class="btn-sm btn-accent">Simpan Booking</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function(){
                // Payment coloring
                function applyPaymentColor(cell){
                    const pay = cell.getAttribute('data-payment');
                    if(!pay) return; // skip if no payment info
                    if(pay==='dp'){
                        cell.style.color = '#faed00';
                        cell.style.fontWeight = '700';
                    } else if(pay==='lunas') {
                        cell.style.color = '#ffffff';
                        cell.style.fontWeight = '700';
                        cell.style.textShadow = '0 0 3px rgba(0,0,0,.6)';
                    }
                }
                document.querySelectorAll('.dash-booking-cell[data-booking-id]')
                    .forEach(c=> applyPaymentColor(c));
                const cellSelector = '.dash-booking-cell';
                let selectedCell = null; let selectedPelangganId = null;
                const modalPelanggan = document.getElementById('modalSelectPelanggan');
                const modalBooking = document.getElementById('modalCreateBooking');
                const btnLanjut = document.getElementById('btnLanjutBooking');
                const pelangganListContainer = document.getElementById('pelangganListContainer');
                const filterInput = document.getElementById('filterPelanggan');
                const formCreatePelanggan = document.getElementById('formCreatePelanggan');
                function openModal(m){ if(!m)return; m.classList.add('show'); m.setAttribute('aria-hidden','false'); }
                function closeModal(m){ if(!m)return; m.classList.remove('show'); m.setAttribute('aria-hidden','true'); }
                document.querySelectorAll('[data-close]').forEach(btn=> btn.addEventListener('click', e=>{closeModal(btn.closest('.dash-modal-overlay'));}));
                [modalPelanggan, modalBooking].forEach(m=> m && m.addEventListener('click', e=> { if(e.target===m) closeModal(m); }));
                document.addEventListener('keydown', e=> { if(e.key==='Escape'){closeModal(modalPelanggan);closeModal(modalBooking);} });

                // Klik sel tabel
                document.querySelectorAll(cellSelector).forEach(td=>{
                    td.addEventListener('click', function(){
                        const status = this.dataset.status;
                        const bookingId = this.dataset.bookingId;
                        selectedCell = this;
                        if(bookingId){
                            // Kalau sudah ada booking -> arahkan ke halaman booking filter hari itu
                            window.location.href = '{{ route('booking.index') }}?tanggal=' + this.dataset.tanggal;
                            return;
                        }
                        // Kosong: mulai proses booking cepat
                        openModal(modalPelanggan);
                    });
                });

                // Filter pelanggan
                filterInput && filterInput.addEventListener('input', function(){
                    const q = this.value.toLowerCase();
                    pelangganListContainer.querySelectorAll('.selectable-row').forEach(row=>{
                        const txt = (row.dataset.nama + ' ' + row.dataset.telepon).toLowerCase();
                        row.style.display = txt.includes(q) ? '' : 'none';
                    });
                });

                // Pilih pelanggan
                pelangganListContainer && pelangganListContainer.addEventListener('click', function(e){
                    const row = e.target.closest('.selectable-row');
                        if(!row) return;
                        pelangganListContainer.querySelectorAll('.selectable-row').forEach(r=> r.classList.remove('active'));
                        row.classList.add('active');
                        selectedPelangganId = row.dataset.id;
                        btnLanjut.disabled = false;
                });

                // Tambah pelanggan baru (AJAX sederhana)
                formCreatePelanggan && formCreatePelanggan.addEventListener('submit', function(e){
                    e.preventDefault();
                    const fd = new FormData(this);
                    fetch('{{ route('penginap.create') }}', {method:'POST', headers:{'X-CSRF-TOKEN': fd.get('_token')}, body:fd})
                        .then(r=>{ if(!r.ok) throw new Error('Gagal'); return r.text(); })
                        .then(()=>{ location.reload(); })
                        .catch(()=> alert('Gagal menambah pelanggan')); // fallback sederhana
                });

                // Dynamic jenis identitas lainnya
                const fpJenis = document.getElementById('fp_jenis_identitas');
                const wrapJenisLain = document.getElementById('wrap_jenis_identitas_lain');
                if(fpJenis && wrapJenisLain){
                    fpJenis.addEventListener('change', ()=>{
                        if(fpJenis.value === 'LAIN'){
                            wrapJenisLain.classList.remove('d-none');
                            const inputLain = wrapJenisLain.querySelector('input');
                            inputLain.required = true;
                            inputLain.focus();
                        } else {
                            wrapJenisLain.classList.add('d-none');
                            const inputLain = wrapJenisLain.querySelector('input');
                            inputLain.value='';
                            inputLain.required = false;
                        }
                    });
                }

                // Lanjut booking setelah pilih pelanggan
                btnLanjut && btnLanjut.addEventListener('click', function(){
                    if(!selectedCell || !selectedPelangganId) return;
                    closeModal(modalPelanggan);
                    // Prefill form booking
                    document.getElementById('qb_pelanggan_id').value = selectedPelangganId;
                    // Preselect kamar yg diklik
                    const selectMulti = document.getElementById('qb_kamar_ids');
                    if(selectMulti){
                        [...selectMulti.options].forEach(o=> o.selected = false);
                        const opt = [...selectMulti.options].find(o=> o.value == selectedCell.dataset.kamarId);
                        if(opt) opt.selected = true;
                    }
                    const tgl = selectedCell.dataset.tanggal;
                    // default check-in 14:00
                    const ci = new Date(tgl + 'T14:00:00');
                    // default check-out +1 day 12:00
                    const co = new Date(tgl + 'T12:00:00');
                    co.setDate(co.getDate()+1);
                    const toLocalDT = (d)=>{
                        const pad=n=> n.toString().padStart(2,'0');
                        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    };
                    document.getElementById('qb_checkin').value = toLocalDT(ci);
                    document.getElementById('qb_checkout').value = toLocalDT(co);
                    openModal(modalBooking);
                });
            })();
        </script>
@endsection