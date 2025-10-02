@extends('layouts.app_layout')

@section('dashboard')
    <div class="container">
      <div class="page-inner">
                <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <h1 class="h1 dashboard-title" style="margin:0;">Dashboard</h1>
                    <div class="dash-month-nav" style="display:flex;align-items:center;gap:8px;">
                        <a class="btn btn-light" href="{{ route('dashboard.index', ['bulan' => $prevMonth, 'tahun' => $prevYear]) }}" title="Bulan Sebelumnya">&laquo;</a>
                        @php
                            $bulanNama = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                        @endphp
                                    <div class="current-month" style="font-weight:800;color:#0f172a;min-width:180px;text-align:center;">
                                        {{ $bulanNama[(int)$bulan] ?? $bulan }} {{ $tahun }}
                        </div>
                        <a class="btn btn-light" href="{{ route('dashboard.index', ['bulan' => $nextMonth, 'tahun' => $nextYear]) }}" title="Bulan Berikutnya">&raquo;</a>
                    </div>
                </div>
        <div class="dash-legend">
            <div class="legend-title">Keterangan</div>
            <div class="legend-items">
                <div class="legend-item"><span class="legend-badge">Pagi</span><span class="legend-text">00:00 – 11:59</span></div>
                <div class="legend-item"><span class="legend-badge">Siang</span><span class="legend-text">12:00 – 23:59</span></div>
                <div class="legend-sep"></div>
                <div class="legend-item"><span class="legend-dot" style="color:#faed00;background:#1f2937;">ID</span><span class="legend-text">DP</span></div>
                <div class="legend-item"><span class="legend-dot pay-lunas">ID</span><span class="legend-text">Lunas</span></div>
                <div class="legend-sep"></div>
                <div class="legend-item"><span class="legend-color swatch-walkin"></span><span class="legend-text">Walk-In</span></div>
                <div class="legend-item"><span class="legend-color swatch-traveloka"></span><span class="legend-text">Online (Traveloka)</span></div>
                <div class="legend-item"><span class="legend-color swatch-agent1"></span><span class="legend-text">Agent 1</span></div>
                <div class="legend-item"><span class="legend-color swatch-agent2"></span><span class="legend-text">Agent 2</span></div>
                <div class="legend-item"><span class="legend-color swatch-cancel"></span><span class="legend-text">Dibatalkan</span></div>
                <div class="legend-sep"></div>
            </div>
        </div>
                    @php $loopJenis = isset($orderedJenisKamar) ? $orderedJenisKamar : $jenisKamar; @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-dashboard ">
                            <thead>
                                <tr class="first-header">
                                    <th class="group-header">Jenis Kamar</th>
                                    @foreach ($loopJenis as $jenis)
                                    @php $jumlahJenis = isset($kamarGrouped) ? ($kamarGrouped[$jenis]->count() ?? 0) : collect($kamarList)->where('tipe', $jenis)->count(); @endphp
                                    <th class="group-header" colspan="{{ $jumlahJenis }}">{{ $jenis }}</th>
                                    @endforeach
                                    <th class="total-col-head" rowspan="2" style="font-size:0.85rem; padding:0 8px; white-space:normal; min-width:90px;">
                                        Total<br>Terisi
                                    </th>
                                </tr>
                                <tr class="second-header">
                                    <th class="tanggal-head" >kamar</th>
                                    @foreach ($loopJenis as $jenis)
                                    @foreach (($kamarGrouped[$jenis] ?? collect()) as $kamar)
                                    <th style="font-size:0.85rem; padding:0 8px; white-space:normal; min-width:90px;">{{ $kamar->nomor_kamar }}</th>
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
                                        $mergedRooms = [];
                                    @endphp
                                    <!-- Morning row -->
                                    <tr class="day-row {{ $isWeekend ? 'weekend' : '' }} morning-row">
                                        <td rowspan="2" class="tanggal-cell">
                                            <span class="day-label">{{ $carbonDate->format('d M') }}</span>
                                            <span class="day-name">{{ $dayName }}</span>
                                        </td>
                                        @foreach ($loopJenis as $jenis)
                                            @foreach (($kamarGrouped[$jenis] ?? collect()) as $kamar)
                                                @php
                                                    $cell = $statusBooking[$tanggal][$kamar->id] ?? ['segments'=>[], 'occ'=>'empty'];
                                                    $segments = $cell['segments'] ?? [];
                                                    $slotMorning = $cell['slot_morning'] ?? [];
                                                    $slotAfternoon = $cell['slot_afternoon'] ?? [];
                                                    $hasMorning = !empty($slotMorning);
                                                    $hasAfternoon = !empty($slotAfternoon);
                                                    // Full-day merge only if SAME booking covers both slots, or a single segment crosses noon
                                                    $coversNoon = false;
                                                    if(!empty($segments)){
                                                        $dayStart = \Carbon\Carbon::parse($tanggal.' 00:00:00');
                                                        $noon = $dayStart->copy()->addHours(12);
                                                        $dayEnd = $dayStart->copy()->addDay();
                                                        foreach($segments as $sg){
                                                            $s = $sg['checkin_at'] ?? $dayStart; $e = $sg['checkout_at'] ?? $dayEnd;
                                                            if($s->lt($noon) && $e->gt($noon)){ $coversNoon = true; break; }
                                                        }
                                                    }
                                                    $sameOrderBoth = ($hasMorning && $hasAfternoon && (($slotMorning[0]['booking_order_id'] ?? null) === ($slotAfternoon[0]['booking_order_id'] ?? null)));
                                                    $fullDay = $sameOrderBoth || $coversNoon;
                                                    // Hitung total terisi harian: full-day ATAU ada segmen pagi/siang
                                                    if($fullDay || $hasMorning || $hasAfternoon){ $totalTerisi++; }
                                                @endphp
                                                @if($fullDay)
                                                    @php 
                                                        $seg = $hasMorning ? $slotMorning[0] : ($segments[0] ?? []); 
                                                        $mergedRooms[$kamar->id] = true; 
                                                        $payClass = ((($seg['payment'] ?? '')==='lunas') ? 'pay-lunas' : 'pay-dp');
                                                    @endphp
                                                    <td class="status-cell dash-booking-cell border-dark" rowspan="2"
                                                            data-tanggal="{{ $tanggal }}"
                                                            data-kamar-id="{{ $kamar->id }}"
                                                            data-booking-id="{{ $seg['booking_order_id'] ?? '' }}"
                                                            title="Full day"
                                                            @php
                                                                $bg = $seg['background'] ?? null; $style='';
                                                                if($bg){ $style .= 'background:'.$bg.';'; }
                                                            @endphp
                                                            style="{{ $style }}">
                                                        <div class="{{ $payClass }} cell-inner">
                                                            {{ $seg['booking_order_id'] ?? '' }}
                                                        </div>
                                                    </td>
                                                @else
                                                    @if($hasMorning)
                                                        @php $seg = $slotMorning[0]; $payClass = ((($seg['payment'] ?? '')==='lunas') ? 'pay-lunas' : 'pay-dp'); @endphp
                                                        <td class="status-cell dash-booking-cell border-dark"
                                                                data-tanggal="{{ $tanggal }}"
                                                                data-kamar-id="{{ $kamar->id }}"
                                                                data-booking-id="{{ $seg['booking_order_id'] ?? '' }}"
                                                                title="Pagi"
                                                                @php
                                                                    $bg = $seg['background'] ?? null; $style='';
                                                                    if($bg){ $style .= 'background:'.$bg.';'; }
                                                                @endphp
                                                                style="{{ $style }}">
                                                            <div class="{{ $payClass }} cell-inner">
                                                                {{ $seg['booking_order_id'] ?? '' }}
                                                            </div>
                                                        </td>
                                                    @else
                                                        <td class="status-cell status-kosong dash-booking-cell border-dark"
                                                                data-tanggal="{{ $tanggal }}"
                                                                data-kamar-id="{{ $kamar->id }}"
                                                                data-booking-id=""
                                                                title="Kosong pagi"></td>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endforeach
                                        <td class="status-cell total-col border-dark">{{ $totalTerisi }}</td>
                                    </tr>
                                    <!-- Afternoon row -->
                                    <tr class="day-row {{ $isWeekend ? 'weekend' : '' }} afternoon-row">
                                        @foreach ($loopJenis as $jenis)
                                            @foreach (($kamarGrouped[$jenis] ?? collect()) as $kamar)
                                                @php
                                                    $cell = $statusBooking[$tanggal][$kamar->id] ?? ['segments'=>[], 'occ'=>'empty'];
                                                    $slotAfternoon = $cell['slot_afternoon'] ?? [];
                                                @endphp
                                                @if(!empty($mergedRooms[$kamar->id] ?? false))
                                                    @continue
                                                @endif
                                                @if(!empty($slotAfternoon))
                                                    @php $seg = $slotAfternoon[0]; $payClass = ((($seg['payment'] ?? '')==='lunas') ? 'pay-lunas' : 'pay-dp'); @endphp
                                                    <td class="status-cell dash-booking-cell border-dark"
                                                            data-tanggal="{{ $tanggal }}"
                                                            data-kamar-id="{{ $kamar->id }}"
                                                            data-booking-id="{{ $seg['booking_order_id'] ?? '' }}"
                                                            title="Siang"
                                                            @php
                                                                $bg = $seg['background'] ?? null; $style='';
                                                                if($bg){ $style .= 'background:'.$bg.';'; }
                                                            @endphp
                                                            style="{{ $style }}">
                                                        <div class="{{ $payClass }} cell-inner">
                                                            {{ $seg['booking_order_id'] ?? '' }}
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="status-cell status-kosong dash-booking-cell border-dark"
                                                            data-tanggal="{{ $tanggal }}"
                                                            data-kamar-id="{{ $kamar->id }}"
                                                            data-booking-id=""
                                                            title="Kosong siang"></td>
                                                @endif
                                            @endforeach
                                        @endforeach
                                        <td class="status-cell total-col border-dark"></td>
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
    </div>
        {{-- Modals for quick booking creation from dashboard --}}
        <style>
            /* Dashboard table - refined look */
            .table-dashboard { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; table-layout: fixed; border: 2px solid #000000; }
            .table-dashboard th, .table-dashboard td { vertical-align: middle; text-align: center; overflow: hidden; text-overflow: ellipsis; }
            .table-dashboard thead th { background: #fff9db; color:#1f2937; font-weight:700; border-bottom: 3px solid rgba(0, 0, 0, .6); padding: 6px 4px; white-space: nowrap; border-right: 2px solid #000000; border-left: 2px solid #000000; }
            .table-dashboard thead th:first-child { border: 2px solid #000000; }
            .first-header .group-header { text-transform: uppercase; letter-spacing: .4px; font-size: .7rem; background: #fde68a; color:#7c2d12; border: 1px solid #000000 !important; }
            .first-header .group-header:first-child { border: 1px solid #000000 !important; }
            .second-header th { font-size: .72rem; color:#7a3d00; background: #fff3bf; border: 1px solid #000000 !important; }
            .second-header th:first-child { border: 1px solid #000000 !important; }
            .table-dashboard tbody td { padding: 0; background:#fff; border-right: 2px solid #000000; border-bottom: 2px solid #000000; }
            .table-dashboard tbody td.border-dark { border-right: 2px solid #000000 !important; border-bottom: 2px solid #000000 !important; }
            .table-dashboard tbody tr:last-child td { border-bottom: 2px solid #000000; }
            .day-row.morning-row td { box-shadow: none; }
            .day-row.weekend td { background: #fbfbff; }
            /* Column sizing */
            .tanggal-head, .tanggal-cell { width: 130px; }
            .tanggal-head { border: 2px solid #000000 !important; }
            /* Ensure the first header cell ("Jenis Kamar") is a bit wider too */
            .first-header .group-header:first-child { width: 130px; }
            .total-col-head, .total-col { width: 96px; }
            /* Row heights for tidy alignment */
            .day-row.morning-row td, .day-row.afternoon-row td { height: 40px; }
            .tanggal-cell { white-space: nowrap; text-align: left !important; padding: 6px 8px !important;  border: 2px solid #000000; background: #f9fafb; }
            .tanggal-cell .day-label { display:block; font-size: .95rem; font-weight: 800; color:#0f172a; line-height: 1.1; }
            .tanggal-cell .day-name { display:block; font-size:.7rem; color:#64748b; margin-top:2px; }
            .dash-booking-cell { position: relative; padding: 0; }
            .dash-booking-cell.status-kosong { background: repeating-linear-gradient(45deg, #f7fafc, #f7fafc 10px, #f1f5f9 10px, #f1f5f9 20px); }
            .dash-booking-cell:hover { outline: 2px solid rgba(13,110,253,.45); outline-offset: -2px; cursor: pointer; }
            .cell-inner { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-weight:700; border-radius: 0; margin: 0; text-shadow: inherit; transition: transform .08s ease-in-out; }
            .dash-booking-cell:hover .cell-inner { transform: translateY(-1px); }
            .pay-dp { color:#faed00; text-shadow:0 0 2px rgba(0,0,0,.6); }
            .pay-lunas { color:#fff; text-shadow:0 0 3px rgba(0,0,0,.6); }
            .total-col-head { background:#fff7ed !important; color:#7c2d12 !important; border: 1px solid #000000 !important; }
            .total-col { background:#fff7ed; color:#7c2d12; font-weight:800; }
            .table-dashboard tbody tr:hover td { background-color: #fafbfd; }
            .table-dashboard tbody tr:hover td.tanggal-cell { background-color: #f1f5f9; }
            /* Legend */
            .dash-legend { display:flex; align-items:center; gap: 12px; padding: 10px 0 8px; flex-wrap: wrap; }
            .legend-title { font-weight: 700; color:#334155; margin-right: 6px; }
            .legend-items { display:flex; align-items:center; gap: 10px; flex-wrap: wrap; }
            .legend-item { display:flex; align-items:center; gap: 6px; color:#475569; font-size: .84rem; }
            .legend-badge { background:#e2e8f0; color:#0f172a; font-weight:700; border-radius: 6px; padding: 2px 8px; font-size:.72rem; }
            .legend-dot { display:inline-flex; align-items:center; justify-content:center; width: 28px; height: 22px; border-radius: 4px; background:#1f2937; color:#fff; font-weight:800; font-size:.72rem; }
            .legend-note { color:#64748b; font-size:.8rem; }
            .legend-sep { width:1px; height:18px; background:#e5e7eb; margin: 0 2px; }
            .legend-color { display:inline-block; width:14px; height:14px; border-radius:3px; box-shadow: 0 0 0 1px rgba(0,0,0,.08) inset; }
            .swatch-walkin { background:#dc3545; }
            .swatch-traveloka { background:#0d6efd; }
            .swatch-agent1 { background:#6f42c1; }
            .swatch-agent2 { background:#198754; }
            .swatch-cancel { background:#555; }
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
            /* Uniform border for each date row */
            .table-dashboard tbody tr.day-row td {
                border-top: 1px solid #0066ffff !important;
                border-bottom: 1px solid #0066ffff !important;
            }
            .table-dashboard tbody tr.day-row td:first-child {
                border-left: 1px solid #0066ffff !important;
            }
            .table-dashboard tbody tr.day-row td:last-child {
                border-right: 1px solid #0066ffff !important;
            }
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
                                <option value="2">Agent 1</option>
                                <option value="3">Agent 2</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Status</label>
                            <select name="status">
                                <option value="1">Dipesan</option>
                                <option value="2">Check-In</option>
                                <option value="3">Check-Out</option>
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
                    if(!pay) return;
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
                const cellSelector = '.dash-booking-cell, .status-cell.has-multi';

                // ===== Restore modal helpers & state =====
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
                ;[modalPelanggan, modalBooking].forEach(m=> m && m.addEventListener('click', e=> { if(e.target===m) closeModal(m); }));
                document.addEventListener('keydown', e=> { if(e.key==='Escape'){closeModal(modalPelanggan);closeModal(modalBooking);} });

                // Klik sel / segmen
                document.querySelectorAll(cellSelector).forEach(td=>{
                    td.addEventListener('click', function(e){
                        const seg = e.target.closest('.cell-seg');
                        const bookingId = seg ? seg.dataset.bookingId : this.dataset.bookingId;
                        selectedCell = this;
                        if(bookingId){
                            window.location.href = '{{ route('booking.index') }}?tanggal=' + this.dataset.tanggal;
                            return;
                        }
                        // Jika tidak ada bookingId -> proses booking cepat
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