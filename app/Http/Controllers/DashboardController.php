<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kamar;
use App\Models\Booking; // legacy (sementara mungkin masih dipakai di tempat lain)
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        // Ambil semua kamar (tanpa urutan abjad tipe agar bisa diurutkan kustom)
        $kamarList = Kamar::all();
        $jenisKamar = $kamarList->pluck('tipe')->unique()->values();
        // Custom LTR order for tipe kamar
        // Put 'non ac' and 'hall' at the very end (HALL as the last)
        $preferred = ['family','superior','twin','standar','standar eco'];
        $prefMap = collect($preferred)->mapWithKeys(fn($v,$i)=> [strtolower(trim($v))=>$i+1])->all();
        $orderedJenisKamar = $jenisKamar->sortBy(function($t) use ($prefMap){
            $keyRaw = (string)$t;
            $key = strtolower(trim($keyRaw));
            // Force terminal positions
            if ($key === 'non ac') { return sprintf('%06d-%s', 999998, $key); }
            if ($key === 'hall')   { return sprintf('%06d-%s', 999999, $key); }
            // Preferred ones get small priority numbers
            if (array_key_exists($key, $prefMap)) {
                return sprintf('%06d-%s', $prefMap[$key], $key);
            }
            // Unknowns come after preferred but before NON AC
            return sprintf('%06d-%s', 900000, $key);
        })->values();
        // Kamar digrup per tipe dan diurutkan natural berdasarkan nomor_kamar
        $kamarGrouped = $kamarList->groupBy('tipe')->map(function($group){
            return $group->sortBy('nomor_kamar', SORT_NATURAL)->values();
        });

        // Ambil semua booking yang overlapped dengan bulan ini (status 1..4)
        // Tujuan: menampilkan SEMUA data booking pada tabel dashboard bulan terkait
        $activeOrders = BookingOrder::with(['items' => function($q){ $q->with('kamar'); }, 'pelanggan'])
            ->whereIn('status',[1,2,3,4])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                         ->where('tanggal_checkout','>=',$end);
                  });
            })
            ->get();

        // Ringkasan metode pemesanan per bulan - MENGHITUNG KAMAR TERISI PER HARI (same as totalKamarTerisiBulan logic)


        // Flatten items with reference to parent order status & dates + meta from payment_status+pemesanan
        $items = [];
        foreach($activeOrders as $order){
            $meta = $order->status_meta; // unified
            // Normalize to day-level range. If check-in and check-out are the same calendar day,
            // extend checkout to next day's 00:00 so the day is included in [checkin, checkout).
            $ciDay = Carbon::parse($order->tanggal_checkin)->startOfDay();
            $coDay = Carbon::parse($order->tanggal_checkout)->startOfDay();
            $ciAt = Carbon::parse($order->tanggal_checkin);
            $coAt = Carbon::parse($order->tanggal_checkout);
            // If checkout has any time after 00:00 on its calendar day, include that day
            // so the morning half-day (e.g., 06:00-12:00) can be rendered.
            // EXCEPTION: exact noon-to-noon (12:00 → next day 12:00) should NOT include checkout day.
            $includeCheckoutDay = false;
            if ($coDay->equalTo($ciDay)) {
                $includeCheckoutDay = true;
            } elseif ($coAt->gt($coDay)) {
                $isNoonToNoon = ($ciAt->format('H:i') === '12:00') && $coAt->equalTo($coDay->copy()->addHours(12));
                $includeCheckoutDay = !$isNoonToNoon;
            }
            if ($includeCheckoutDay) {
                $coDay = $coDay->copy()->addDay();
            }
            foreach($order->items as $it){
                $code = (string)($order->order_code ?? '');
                $short = strlen($code) >= 3 ? substr($code, -3) : $code;
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status, // 1..4
                    'booking_order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_code_short' => $short,
                    'pemesanan' => $order->pemesanan, // 0=walk-in, 1=traveloka, 2-3=agen
                    // status_code removed
                    'meta' => $meta,
                    'checkin' => $ciDay,
                    'checkout' => $coDay,
                    'checkin_at' => $ciAt,
                    'checkout_at' => $coAt,
                    'is_multi_day_booking' => $coDay->greaterThan($ciDay), // Add this flag
                ];
            }
        }
        // Group items by kamar_id for faster lookup
        $itemsByKamar = collect($items)->groupBy('kamar_id');

        // Siapkan list tanggal harian (untuk tampilan dashboard)
        $tanggalList = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $tanggalList[] = $cursor->format('Y-m-d');
        }

        // List tanggal untuk perhitungan okupansi (hanya hari yang sudah lewat dan hari ini)
        $today = Carbon::now()->startOfDay();
        $tanggalListForOccupancy = [];
        for ($cursor = $start->copy(); $cursor->lte($end) && $cursor->lte($today); $cursor->addDay()) {
            $tanggalListForOccupancy[] = $cursor->format('Y-m-d');
        }

    $statusBooking = []; // [tanggal][kamar_id] => ['status'=>kode, 'booking_id'=>id]
        $totalKamarTerisiBulan = 0;

        foreach ($tanggalList as $tgl) {
            $carbonDate = Carbon::parse($tgl);
            foreach ($kamarList as $kamar) {
                $segments = [];
                $slotMorning = [];
                $slotAfternoon = [];
                $isOccupied = false;
                $isMultiDayBookingForCell = false; // Aggregate multi-day status for the cell
                if(isset($itemsByKamar[$kamar->id])){
                    foreach($itemsByKamar[$kamar->id] as $row){
                        if($carbonDate->gte($row['checkin']) && $carbonDate->lt($row['checkout'])){
                            // Hitung porsi (fraction) dari hari ini yang ditempati oleh segmen ini
                            $dayStart = $carbonDate->copy()->startOfDay();
                            $dayEnd = $dayStart->copy()->addDay();
                            $morningStart = $dayStart->copy()->addHours(6);
                            $segStart = $row['checkin_at']->greaterThan($dayStart) ? $row['checkin_at'] : $dayStart;
                            $segEnd = $row['checkout_at']->lessThan($dayEnd) ? $row['checkout_at'] : $dayEnd;
                            $duration = max(0, $segEnd->diffInSeconds($segStart));
                            $fraction = $duration > 0 ? min(1, $duration / 86400) : 0;

                            // Determine if this segment is a half-day checkout on the current date
                            $isHalfDayCheckout = false;
                            if ($row['checkout_at']->format('Y-m-d H:i') === $carbonDate->format('Y-m-d').' 12:00') {
                                $isHalfDayCheckout = true;
                            }
                            
                            if ($row['is_multi_day_booking']) {
                                $isMultiDayBookingForCell = true;
                            }

                            $segments[] = [
                                'booking_order_id' => $row['booking_order_id'],
                                'booking_code' => $row['order_code'] ?? null,
                                'booking_code_short' => $row['order_code_short'] ?? null,
                                'status' => $row['status'], // 1..4
                                'pemesanan' => $row['pemesanan'] ?? 0, // 0=walk-in, 1=traveloka, 2-3=agen
                                'payment' => $row['meta']['payment'] ?? null,
                                'channel' => $row['meta']['channel'] ?? null,
                                'background' => $row['meta']['background'] ?? null,
                                'text_color' => $row['meta']['text_color'] ?? null,
                                'checkin_at' => $row['checkin_at'],
                                'checkout_at' => $row['checkout_at'],
                                'fraction' => $fraction,
                                'is_half_day_checkout' => $isHalfDayCheckout, // Add this flag
                                'is_multi_day_booking' => $row['is_multi_day_booking'], // Pass multi-day status
                            ];

                            $rawIn = $row['checkin_at'];
                            $rawOut = $row['checkout_at'];

                            $currentDayStart = $carbonDate->copy()->startOfDay();

                            // Slot Pagi: 06:00 - 11:59
                            $morningSlotStart = $currentDayStart->copy()->addHours(6);
                            $morningSlotEnd = $currentDayStart->copy()->addHours(18); // Exclusive, so up to 11:59:59

                            // Slot Sore: 12:00 - 23:59
                            $afternoonSlotStart = $currentDayStart->copy()->addHours(18);
                            $afternoonSlotEnd = $currentDayStart->copy()->addHours(24); // Exclusive, so up to 23:59:59 (which is 00:00 next day)

                            // Check for Morning Slot overlap
                            if ($rawOut->greaterThan($morningSlotStart) && $rawIn->lessThan($morningSlotEnd)) {
                                $slotMorning[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'booking_code_short' => $row['order_code_short'] ?? null,
                                    'status' => $row['status'],
                                    'pemesanan' => $row['pemesanan'] ?? 0,
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                    'is_half_day_checkout' => $isHalfDayCheckout, // Add this flag
                                    'is_multi_day_booking' => $row['is_multi_day_booking'], // Pass multi-day status
                                ];
                            }

                            // Check for Afternoon Slot overlap
                            // Only add to afternoon slot if it's NOT a half-day checkout on this specific day
                            if ($rawOut->greaterThan($afternoonSlotStart) && $rawIn->lessThan($afternoonSlotEnd) && !$isHalfDayCheckout) {
                                $slotAfternoon[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'booking_code_short' => $row['order_code_short'] ?? null,
                                    'status' => $row['status'],
                                    'pemesanan' => $row['pemesanan'] ?? 0,
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null, 'text_color' => $row['meta']['text_color'] ?? null,
                                    'is_half_day_checkout' => $isHalfDayCheckout, // Add this flag
                                    'is_multi_day_booking' => $row['is_multi_day_booking'], // Pass multi-day status
                                ];
                            }

                            if($row['status'] == 2) $isOccupied = true;
                        }
                    }
                }
                // Urutkan segmen berdasarkan waktu check-in aktual (terlebih dahulu tampil di atas)
                usort($segments, function($a,$b){
                    if($a['checkin_at'] == $b['checkin_at']) return 0;
                    return ($a['checkin_at'] < $b['checkin_at']) ? -1 : 1;
                });

                $statusBooking[$tgl][$kamar->id] = [
                    'segments' => $segments,
                    'occ' => $isOccupied ? 'occupied' : (count($segments) ? 'booked' : 'empty'),
                    'slot_morning' => $slotMorning,
                    'slot_afternoon' => $slotAfternoon,
                    'is_multi_day' => $isMultiDayBookingForCell, // Pass aggregated multi-day status
                ];
                // Monthly total: count as occupied if ANY usage on this day (morning or afternoon slot exists)
                // or a segment crosses noon (full-day), so half-day and check-out days are included.
                $dayStart = $carbonDate->copy()->startOfDay();
                $noon = $dayStart->copy()->addHours(12);
                $dayEnd = $dayStart->copy()->addDay();
                $hasMorning = !empty($slotMorning);
                $hasAfternoon = !empty($slotAfternoon);
                $coversNoon = false;
                foreach($segments as $sg){
                    $s = $sg['checkin_at'] ?? $dayStart; $e = $sg['checkout_at'] ?? $dayEnd;
                    if($s < $noon && $e > $noon){ $coversNoon = true; break; }
                }
                if($coversNoon || $hasMorning || $hasAfternoon){
                    $totalKamarTerisiBulan++;
                }
            }
        }

        // Hitung ringkasan metode pemesanan (Walk-In, Traveloka, Agen)
        // Gunakan logika yang sama dengan totalKamarTerisiBulan
        $methodCounts = ['walk_in' => 0, 'traveloka' => 0, 'agen' => 0];
        $debugLog = [];
        // Akumulasi harian untuk rata-rata persentase per hari
        $dayTotals = [];
        $dayMethodCounts = [];
        // Filter kamar: abaikan HALL dan Non AC D11
        $availableRooms = $kamarList->filter(function($km){
            $nomor = strtolower(trim($km->nomor_kamar ?? ''));
            $tipe = strtolower(trim($km->tipe ?? ''));
            $isHall = (bool)preg_match('/^hall$/i', $tipe);
            $isNonACD11 = (bool)preg_match('/non\s*ac\s*d11/i', $nomor);
            return !($isHall || $isNonACD11);
        });
        foreach ($tanggalListForOccupancy as $tgl) {
            $carbonDate = Carbon::parse($tgl);
            $dayStart = $carbonDate->copy()->startOfDay();
            $noon = $dayStart->copy()->addHours(12);
            $dayEnd = $dayStart->copy()->addDay();
            // Init per-hari
            $dayTotals[$tgl] = 0;
            $dayMethodCounts[$tgl] = ['walk_in' => 0, 'traveloka' => 0, 'agen' => 0];
            
            foreach ($availableRooms as $kamar) {
                if (isset($statusBooking[$tgl][$kamar->id])) {
                    $data = $statusBooking[$tgl][$kamar->id];
                    $segments = $data['segments'];
                    $slotMorning = $data['slot_morning'];
                    $slotAfternoon = $data['slot_afternoon'];
                    
                    // Cek jika ada occupancy dengan logika yang sama: morning + afternoon slots atau covers noon
                    $hasMorning = !empty($slotMorning);
                    $hasAfternoon = !empty($slotAfternoon);
                    $coversNoon = false;
                    $occupiedSegment = null;
                    
                    foreach($segments as $sg){
                        $s = $sg['checkin_at'] ?? $dayStart; 
                        $e = $sg['checkout_at'] ?? $dayEnd;
                        if($s < $noon && $e > $noon){ 
                            $coversNoon = true; 
                        }
                        // Simpan segment yang memiliki status occupied
                        if ($sg['status'] == 2 && $occupiedSegment === null) {
                            $occupiedSegment = $sg;
                        }
                    }
                    
                    if($coversNoon || $hasMorning || $hasAfternoon || $occupiedSegment){
                        // Ada occupancy, ambil method dari slot atau segment
                        $pemesanan = null;
                        
                        // Coba ambil dari morning slot
                        if (!empty($slotMorning)) {
                            $pemesanan = (int)($slotMorning[0]['pemesanan'] ?? 0);
                        }
                        // Atau dari afternoon slot
                        elseif (!empty($slotAfternoon)) {
                            $pemesanan = (int)($slotAfternoon[0]['pemesanan'] ?? 0);
                        }
                        // Atau dari occupied segment
                        elseif ($occupiedSegment) {
                            $pemesanan = (int)($occupiedSegment['pemesanan'] ?? 0);
                        }
                        // Atau dari segment pertama
                        elseif (!empty($segments)) {
                            $pemesanan = (int)($segments[0]['pemesanan'] ?? 0);
                        }
                        
                        if ($pemesanan !== null) {
                            $debugLog[] = [
                                'date' => $tgl,
                                'kamar_id' => $kamar->id,
                                'pemesanan' => $pemesanan,
                                'hasMorning' => $hasMorning,
                                'hasAfternoon' => $hasAfternoon,
                                'coversNoon' => $coversNoon,
                                'hasOccupiedSegment' => !!$occupiedSegment,
                                'slotMorningCount' => count($slotMorning),
                                'slotAfternoonCount' => count($slotAfternoon),
                                'segmentCount' => count($segments),
                            ];
                            
                            if ($pemesanan === 0) {
                                $methodCounts['walk_in']++;
                                $dayMethodCounts[$tgl]['walk_in']++;
                            } elseif ($pemesanan === 1) {
                                $methodCounts['traveloka']++;
                                $dayMethodCounts[$tgl]['traveloka']++;
                            } elseif (in_array($pemesanan, [2, 3])) {
                                $methodCounts['agen']++;
                                $dayMethodCounts[$tgl]['agen']++;
                            }
                            // Tambah total kamar terisi hari ini
                            $dayTotals[$tgl]++;
                        }
                    }
                }
            }
        }
        
        // Debug logging
        \Log::info('Method Summary Debug - Counts', [
            'methodCounts' => $methodCounts,
            'totalRecords' => count($debugLog),
        ]);
        \Log::info('Method Summary Debug - Details', $debugLog);
        
        $methodTotal = array_sum($methodCounts);
        $methodPercents = [];
        foreach ($methodCounts as $method => $count) {
            $methodPercents[$method] = $methodTotal > 0 ? round(($count / $methodTotal) * 100, 1) : 0;
        }

        // Rata-rata persentase per hari untuk setiap metode
        $methodDailyPercents = ['walk_in' => 0, 'traveloka' => 0, 'agen' => 0];
        $daysWithOcc = 0;
        $sumPercents = ['walk_in' => 0, 'traveloka' => 0, 'agen' => 0];
        foreach ($tanggalListForOccupancy as $tgl) {
            $dayTotal = $dayTotals[$tgl] ?? 0;
            if ($dayTotal > 0) {
                $daysWithOcc++;
                foreach (['walk_in','traveloka','agen'] as $m) {
                    $sumPercents[$m] += (($dayMethodCounts[$tgl][$m] ?? 0) / $dayTotal) * 100;
                }
            }
        }
        foreach (['walk_in','traveloka','agen'] as $m) {
            $methodDailyPercents[$m] = $daysWithOcc > 0 ? round($sumPercents[$m] / $daysWithOcc, 1) : 0;
        }

        // Rata-rata harian keseluruhan dalam persen (dibanding total kamar)
        // Gunakan 17 kamar sebagai denominator (abaikan HALL dan Non AC D11)
        // Hanya hitung hari yang sudah lewat dan hari ini
        $roomCount = 17;
        $avgDailyPercentTotal = 0;
        $dayCount = count($tanggalListForOccupancy);
        if ($roomCount > 0 && $dayCount > 0) {
            $sumDailyPercent = 0;
            foreach ($tanggalListForOccupancy as $tgl) {
                $sumDailyPercent += (($dayTotals[$tgl] ?? 0) / $roomCount) * 100;
            }
            $avgDailyPercentTotal = round(($sumDailyPercent / $dayCount)*100, 1);
        }

        // Rata-rata kamar terisi per hari (total dan per metode)
        $daysInMonth = count($tanggalList);
        $avgPerDayTotal = $daysInMonth > 0 ? round($methodTotal / $daysInMonth, 1) : 0;
        $methodAverages = [];
        foreach ($methodCounts as $method => $count) {
            $methodAverages[$method] = $daysInMonth > 0 ? round($count / $daysInMonth, 1) : 0;
        }

        // Data navigasi bulan sebelumnya / berikutnya
        $prevMonth = $bulan - 1 < 1 ? 12 : $bulan - 1;
        $prevYear = $bulan - 1 < 1 ? $tahun - 1 : $tahun;
        $nextMonth = $bulan + 1 > 12 ? 1 : $bulan + 1;
        $nextYear = $bulan + 1 > 12 ? $tahun + 1 : $tahun;

        return view('dashboard', compact(
            'bulan','tahun','prevMonth','prevYear','nextMonth','nextYear',
            'kamarList','jenisKamar','orderedJenisKamar','kamarGrouped',
            'tanggalList','statusBooking','totalKamarTerisiBulan',
            'methodCounts','methodPercents','methodTotal',
            'avgPerDayTotal','methodAverages','methodDailyPercents','avgDailyPercentTotal'
        ));
    }
}
