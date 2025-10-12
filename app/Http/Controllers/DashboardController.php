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
        $preferred = ['family','superior','twin','standar','standar eco','non ac','HALL'];
        $prefMap = collect($preferred)->mapWithKeys(fn($v,$i)=> [strtolower(trim($v))=>$i+1])->all();
        $orderedJenisKamar = $jenisKamar->sortBy(function($t) use ($prefMap){
            $keyRaw = (string)$t;
            $key = strtolower(trim($keyRaw));
            // Force NON AC to be the last
            if ($key === 'non ac') {
                return sprintf('%06d-%s', 999999, $key);
            }
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
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status, // 1..4
                    'booking_order_id' => $order->id,
                    'order_code' => $order->order_code,
                    // status_code removed
                    'meta' => $meta,
                    'checkin' => $ciDay,
                    'checkout' => $coDay,
                    'checkin_at' => $ciAt,
                    'checkout_at' => $coAt,
                ];
            }
        }
        // Group items by kamar_id for faster lookup
        $itemsByKamar = collect($items)->groupBy('kamar_id');

        // Siapkan list tanggal harian
        $tanggalList = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $tanggalList[] = $cursor->format('Y-m-d');
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
                if(isset($itemsByKamar[$kamar->id])){
                    foreach($itemsByKamar[$kamar->id] as $row){
                        if($carbonDate->gte($row['checkin']) && $carbonDate->lt($row['checkout'])){
                            // Hitung porsi (fraction) dari hari ini yang ditempati oleh segmen ini
                            $dayStart = $carbonDate->copy()->startOfDay();
                            $dayEnd = $dayStart->copy()->addDay();
                            $morningStart = $dayStart->copy()->addHours(6);
                            $morningEnd = $dayStart->copy()->addHours(12);
                            $afternoonStart = $dayStart->copy()->addHours(12);
                            $afternoonEnd = $dayStart->copy()->addHours(18);
                            $segStart = $row['checkin_at']->greaterThan($dayStart) ? $row['checkin_at'] : $dayStart;
                            $segEnd = $row['checkout_at']->lessThan($dayEnd) ? $row['checkout_at'] : $dayEnd;
                            $duration = max(0, $segEnd->diffInSeconds($segStart));
                            $fraction = $duration > 0 ? min(1, $duration / 86400) : 0;

                            $segments[] = [
                                'booking_order_id' => $row['booking_order_id'],
                                'booking_code' => $row['order_code'] ?? null,
                                'status' => $row['status'], // 1..4
                                'payment' => $row['meta']['payment'] ?? null,
                                'channel' => $row['meta']['channel'] ?? null,
                                'background' => $row['meta']['background'] ?? null,
                                'text_color' => $row['meta']['text_color'] ?? null,
                                'checkin_at' => $row['checkin_at'],
                                'checkout_at' => $row['checkout_at'],
                                'fraction' => $fraction,
                            ];

                            // Special case: 12:00 today -> >= 12:00 next day should paint FULL day on this date
                            $rawIn = $row['checkin_at'];
                            $rawOut = $row['checkout_at'];
                            $noonToNextDayOrMore = ($rawIn->equalTo($afternoonStart) && $rawOut->gte($afternoonStart->copy()->addDay()));
                            if ($noonToNextDayOrMore) {
                                $slotMorning[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'status' => $row['status'],
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                ];
                                $slotAfternoon[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'status' => $row['status'],
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                ];
                            } else {
                                // Overlap-based marking
                                if ($segEnd > $morningStart && $segStart < $morningEnd) {
                                    $slotMorning[] = [
                                        'booking_order_id' => $row['booking_order_id'],
                                        'booking_code' => $row['order_code'] ?? null,
                                        'status' => $row['status'],
                                        'payment' => $row['meta']['payment'] ?? null,
                                        'background' => $row['meta']['background'] ?? null,
                                        'text_color' => $row['meta']['text_color'] ?? null,
                                    ];
                                }
                                // Suppress next-day afternoon for pattern: 12:00 day-1 -> 18:00 this day
                                $suppressThisAfternoon = ($rawIn->equalTo($dayStart->copy()->subDay()->addHours(12)) && $rawOut->equalTo($afternoonEnd));
                                if ($segEnd > $afternoonStart && $segStart < $afternoonEnd && !$suppressThisAfternoon) {
                                    $slotAfternoon[] = [
                                        'booking_order_id' => $row['booking_order_id'],
                                        'booking_code' => $row['order_code'] ?? null,
                                        'status' => $row['status'],
                                        'payment' => $row['meta']['payment'] ?? null,
                                        'background' => $row['meta']['background'] ?? null,
                                        'text_color' => $row['meta']['text_color'] ?? null,
                                    ];
                                }
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

        // Data navigasi bulan sebelumnya / berikutnya
        $prevMonth = $bulan - 1 < 1 ? 12 : $bulan - 1;
        $prevYear = $bulan - 1 < 1 ? $tahun - 1 : $tahun;
        $nextMonth = $bulan + 1 > 12 ? 1 : $bulan + 1;
        $nextYear = $bulan + 1 > 12 ? $tahun + 1 : $tahun;

        return view('dashboard', compact(
            'bulan','tahun','prevMonth','prevYear','nextMonth','nextYear',
            'kamarList','jenisKamar','orderedJenisKamar','kamarGrouped',
            'tanggalList','statusBooking','totalKamarTerisiBulan'
        ));
    }
}
