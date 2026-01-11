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
                    <!-- Ringkasan metode pemesanan per bulan -->
                    <div class="dash-method-summary" style="display:flex;align-items:center;gap:8px;">
                        <div class="method-card" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:8px 10px;">
                            <div style="font-weight:700;color:#334155;margin-bottom:6px;font-size:.85rem;">Ringkasan Metode - Kamar Terisi</div>
                            <table style="border-collapse:collapse;font-size:.82rem;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;color:#64748b;font-weight:600;padding:4px 6px;">Metode</th>
                                        <th style="text-align:right;color:#64748b;font-weight:600;padding:4px 6px;">Kamar</th>
                                        <th style="text-align:right;color:#64748b;font-weight:600;padding:4px 6px;">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding:3px 6px;color:#0f172a;">Walk-In</td>
                                        <td style="padding:3px 6px;text-align:right;color:#0f172a;">{{ $methodCounts['walk_in'] ?? 0 }}</td>
                                        <td style="padding:3px 6px;text-align:right;color:#0f172a;">{{ $methodPercents['walk_in'] ?? 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:3px 6px;color:#0f172a;">Traveloka</td>
                                        <td style="padding:3px 6px;text-align:right;color:#0f172a;">{{ $methodCounts['traveloka'] ?? 0 }}</td>
                                        <td style="padding:3px 6px;text-align:right;color:#0f172a;">{{ $methodPercents['traveloka'] ?? 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:3px 6px;color:#0f172a;">Agen</td>
                                        <td style="padding:3px 6px;text-align:right;color:#0f172a;">{{ $methodCounts['agen'] ?? 0 }}</td>
                                        <td style="padding:3px 6px;text-align:right;color:#0f172a;">{{ $methodPercents['agen'] ?? 0 }}%</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td style="padding:4px 6px;color:#334155;font-weight:700;">Total</td>
                                        <td style="padding:4px 6px;text-align:right;color:#334155;font-weight:700;">{{ $methodTotal ?? 0 }}</td>
                                        <td style="padding:4px 6px;text-align:right;color:#334155;font-weight:700;">{{ ($methodTotal ?? 0) > 0 ? '100%' : '0%' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 6px;color:#334155;font-weight:700;">Rata-rata/hari</td>
                                        <td style="padding:4px 6px;text-align:right;color:#334155;font-weight:700;">{{ $avgPerDayTotal ?? 0 }}</td>
                                        <td style="padding:4px 6px;text-align:right;color:#334155;font-weight:700;">{{ $avgDailyPercentTotal ?? 0 }}%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
        <div class="dash-legend">
            <div class="legend-title">Keterangan</div>
            <div class="legend-items">
                <div class="legend-item"><span class="legend-badge">Pagi</span><span class="legend-text">06:00 – 11:59</span></div>
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
                    <div class="dash-table-wrap">
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
                                                    $isMultiDay = $cell['is_multi_day'] ?? false;
                                                    $hasMorning = !empty($slotMorning);
                                                    $hasAfternoon = !empty($slotAfternoon);
                                                    
                                                    $isHalfDayCheckoutToday = false;
                                                    $isCheckinDay = false;
                                                    $isIntermediateDay = false;

                                                    if(!empty($segments)){
                                                        foreach($segments as $sg){
                                                            // Check if this segment is a half-day checkout on the current day
                                                            if (($sg['is_half_day_checkout'] ?? false) && $sg['checkout_at']->format('Y-m-d') === $tanggal) {
                                                                $isHalfDayCheckoutToday = true;
                                                            }
                                                            // Check if this is the check-in day for this segment
                                                            if ($sg['checkin_at']->format('Y-m-d') === $tanggal) {
                                                                $isCheckinDay = true;
                                                            }
                                                            // Check if this is an intermediate day (not check-in, not check-out, but within booking range)
                                                            if ($sg['checkin_at']->format('Y-m-d') < $tanggal && $sg['checkout_at']->format('Y-m-d') > $tanggal) {
                                                                $isIntermediateDay = true;
                                                            }
                                                        }
                                                    }

                                                    // Determine if the cell should be merged (rowspan=2)
                                                    $fullDay = false;
                                                    if ($isMultiDay) {
                                                        if ($isCheckinDay) {
                                                            // If it's the check-in day of a multi-day booking, it's always full day
                                                            $fullDay = true;
                                                        } elseif ($isIntermediateDay) {
                                                            // If it's an intermediate day of a multi-day booking, it's full day
                                                            $fullDay = true;
                                                        } elseif ($hasMorning && $hasAfternoon && !$isHalfDayCheckoutToday) {
                                                            // If it's the checkout day of a multi-day booking, and checkout is after noon
                                                            $fullDay = true;
                                                        }
                                                    } else {
                                                        // For single-day bookings, it's full day if both slots are occupied
                                                        $fullDay = $hasMorning && $hasAfternoon;
                                                    }
                                                    
                                                    // Hitung total terisi harian: full-day ATAU ada segmen pagi/siang
                                                    if($fullDay || $hasMorning || $hasAfternoon){ $totalTerisi++; }
                                                @endphp
                                                @if($fullDay)
                                                    @php 
                                                        $seg = $hasMorning ? $slotMorning[0] : ($segments[0] ?? []); 
                                                        $mergedRooms[$kamar->id] = true; 
                                                        $payClass = ((($seg['payment'] ?? '')==='lunas') ? 'pay-lunas' : 'pay-dp');
                                                    @endphp
                                                    <td class="status-cell dash-booking-cell border-dark" rowspan="2" data-status="{{ $seg['status'] ?? '' }}"
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
                                                            {{ $seg['booking_code_short'] ?? $seg['booking_order_id'] ?? '' }}
                                                        </div>
                                                    </td>
                                                @else
                                                    @if($hasMorning)
                                                        @php $seg = $slotMorning[0]; $payClass = ((($seg['payment'] ?? '')==='lunas') ? 'pay-lunas' : 'pay-dp'); @endphp
                                                        <td class="status-cell dash-booking-cell border-dark"
                                                                data-status="{{ $seg['status'] ?? '' }}"
                                                                data-tanggal="{{ $tanggal }}"
                                                                data-kamar-id="{{ $kamar->id }}"
                                                                data-booking-id="{{ $seg['booking_order_id'] ?? '' }}"
                                                                title="Pagi"
                                                                @php
                                                                    $bg = $seg['background'] ?? null; $style='';
                                                                    if($bg){ $style .= 'background:'.$bg.';'; }
                                                                @endphp
                                                                style="{{ $style }}">
                                                            <div class="{{ $payClass }} cell-inner" data-status="{{ $seg['status'] ?? '' }}">
                                                                {{ $seg['booking_code_short'] ?? $seg['booking_order_id'] ?? '' }}
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
                                                    // Check if any segment for this kamar on this day is a half-day checkout
                                                    $isHalfDayCheckoutForKamar = false;
                                                    foreach ($cell['segments'] as $seg) {
                                                        if (($seg['is_half_day_checkout'] ?? false) && $seg['checkout_at']->format('Y-m-d') === $tanggal) {
                                                            $isHalfDayCheckoutForKamar = true;
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                @if(!empty($mergedRooms[$kamar->id] ?? false))
                                                    @continue
                                                @endif
                                                {{-- If it's a half-day checkout for this kamar on this day, the afternoon slot should be empty --}}
                                                @if(!empty($slotAfternoon) && !$isHalfDayCheckoutForKamar)
                                                    @php $seg = $slotAfternoon[0]; $payClass = ((($seg['payment'] ?? '')==='lunas') ? 'pay-lunas' : 'pay-dp'); @endphp
                                                    <td class="status-cell dash-booking-cell border-dark"
                                                            data-status="{{ $seg['status'] ?? '' }}"
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
                                                            {{ $seg['booking_code_short'] ?? '' }}
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
        {{-- Removed modals: direct cell click will go to create booking --}}
        <style>
            /* Dashboard table - refined look */
            .table-dashboard { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; table-layout: fixed; border: 2px solid #000000; }
            .table-dashboard th, .table-dashboard td { vertical-align: middle; text-align: center; overflow: hidden; text-overflow: ellipsis; }
            .table-dashboard thead th { background: #fff9db; color:#1f2937; font-weight:700; border-bottom: 3px solid rgba(0, 0, 0, .6); padding: 6px 4px; white-space: nowrap; border-right: 2px solid #000000; border-left: 2px solid #000000; }
            /* Sticky headers */
            .dash-table-wrap { position: relative; height: 70vh; overflow: auto; }
            .table-dashboard thead tr.first-header th { position: sticky; top: 0; z-index: 20; background: #fff9db; }
            /* second header sits just below the first; JS sets --hdr1h dynamically */
            .table-dashboard thead tr.second-header th { position: sticky; top: var(--hdr1h, 40px); z-index: 19; background: #fff3bf; }
            .table-dashboard thead th:first-child { border: 2px solid #000000; }
            .first-header .group-header { text-transform: uppercase; letter-spacing: .4px; font-size: .7rem; background: #fde68a; color:#7c2d12; border: 1px solid #000000 !important; }
            .first-header .group-header:first-child { border: 1px solid #000000 !important; }
            .second-header th { font-size: .72rem; color:#7a3d00; background: #fff3bf; border: 1px solid #000000 !important; }
            .second-header th:first-child { border: 1px solid #000000 !important; }
            .table-dashboard tbody td { padding: 0; border-right: 2px solid #000000; border-bottom: 2px solid #000000; }
            .dash-booking-cell:not(.status-kosong) { background: #1f1f1f; }
            .table-dashboard tbody td.border-dark { border-right: 2px solid #000000 !important; border-bottom: 2px solid #000000 !important; }
            .table-dashboard tbody tr:last-child td { border-bottom: 2px solid #000000; }
            .day-row.morning-row td { box-shadow: none; }
            .day-row.weekend .status-kosong { background: #fbfbff; }
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
            .dash-booking-cell.status-kosong { background: #ffffff; }
            .dash-booking-cell:hover { outline: 2px solid rgba(13,110,253,.45); outline-offset: -2px; cursor: pointer; }
            .cell-inner { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-weight:700; border-radius: 0; margin: 0; text-shadow: inherit; transition: transform .08s ease-in-out; }
            .dash-booking-cell:hover .cell-inner { transform: translateY(-1px); }
            /* Status colors for booking cells */
            .dash-booking-cell[data-status='1'] { background: #1f1f1f; }
            .dash-booking-cell[data-status='1'] .cell-inner { 
                color: #000000; 
                text-shadow: 0 0 2px rgba(255,255,255,.8); 
            } /* Dipesan */
            
            .dash-booking-cell[data-status='2'] { background: #1f1f1f; }
            .dash-booking-cell[data-status='2'] .cell-inner { 
                color: #faed00; 
                text-shadow: 0 0 2px rgba(0,0,0,.6); 
            } /* Check-in */
            
            .dash-booking-cell[data-status='3'] { background: #1f1f1f; }
            .dash-booking-cell[data-status='3'] .cell-inner { 
                color: #ffffff; 
                text-shadow: 0 0 2px rgba(0,0,0,.6); 
            } /* Check-out */
            
            /* Override untuk sel kosong */
            .dash-booking-cell.status-kosong {
                background: #ffffff;
            }
            .total-col-head { background:#fff7ed !important; color:#7c2d12 !important; border: 1px solid #000000 !important; }
            .total-col { background:#fff7ed; color:#7c2d12; font-weight:800; }
            .table-dashboard tbody tr:hover td.status-kosong { background-color: #fafbfd; }
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
                border-top: 1px solid rgb(0, 0, 0) !important;
                border-bottom: 1px solid rgb(0, 0, 0) !important;
            }
            .table-dashboard tbody tr.day-row td:first-child {
                border-left: 1px solid rgb(0, 0, 0) !important;
            }
            .table-dashboard tbody tr.day-row td:last-child {
                border-right: 1px solid rgb(0, 0, 0) !important;
            }
        </style>
        
        <script>
            (function(){
                // Handle click on booking cells
                document.querySelectorAll('.dash-booking-cell').forEach(td => {
                    td.addEventListener('click', () => {
                        const bookingId = td.getAttribute('data-booking-id');
                        const kamarId = td.getAttribute('data-kamar-id');
                        const tanggal = td.getAttribute('data-tanggal'); // format YYYY-MM-DD
                        const isMorning = td.parentElement?.classList.contains('morning-row');

                        // Jika ada booking ID, arahkan ke halaman detail booking (langsung ke /booking/{id}/detail)
                        if (bookingId) {
                            window.location.href = `{{ url('/booking') }}/${bookingId}/detail`;
                            return;
                        }

                        // Jika sel kosong, arahkan ke form pembuatan booking baru
                        const checkin = `${tanggal}T${isMorning ? '06:00' : '12:00'}`;
                        const checkout = `${tanggal}T${isMorning ? '12:00' : '23:59'}`;
                        const params = new URLSearchParams();
                        if (kamarId) { params.append('kamar_ids[]', kamarId); }
                        params.set('tanggal_checkin', checkin);
                        params.set('tanggal_checkout', checkout);
                        params.set('pemesanan', '0'); // walk-in
                        params.set('jumlah_tamu', '1');

                        const url = `{{ route('booking.create') }}?${params.toString()}`;
                        window.location.href = url;
                    });
                });
                // Compute sticky offset for the second header based on actual height of the first header row
                function setStickyOffsets(){
                    const wrap = document.querySelector('.dash-table-wrap');
                    const firstHdr = document.querySelector('.table-dashboard thead tr.first-header');
                    if(wrap && firstHdr){
                        const h = firstHdr.getBoundingClientRect().height || firstHdr.offsetHeight || 40;
                        wrap.style.setProperty('--hdr1h', h + 'px');
                    }
                }
                setStickyOffsets();
                window.addEventListener('resize', setStickyOffsets);
            })();
        </script>
@endsection
