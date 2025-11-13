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
            // Range untuk loop dashboard: [checkin_day, checkout_day)
            // Jika checkout adalah 18:00 (belum tengah malam), checkout_day tetap included
            // Logic: jika checkout tepat di tengah malam (00:00), jangan include checkout day
            $coDay_endExclusive = $coDay->copy(); // default: checkout day tidak included
            if ($coAt->gt($coDay)) {
                // Checkout punya waktu sisa di hari itu (tidak tengah malam), jadi include hari checkout
                $coDay_endExclusive = $coDay->copy()->addDay();
            }
            // Gunakan ini untuk range loop
            $coDay = $coDay_endExclusive->copy()->subDay(); // untuk display/comparison
            foreach($order->items as $it){
                $code = (string)($order->order_code ?? '');
                $short = strlen($code) >= 3 ? substr($code, -3) : $code;
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status, // 1..4
                    'booking_order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_code_short' => $short,
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
                        // Use raw checkin/checkout times for precise overlap check
                        $rawIn = $row['checkin_at'];
                        $rawOut = $row['checkout_at'];
                        $dayStart = $carbonDate->copy()->startOfDay();
                        $dayEnd = $carbonDate->copy()->endOfDay();

                        // Check if the booking range [rawIn, rawOut) overlaps with the current day's range [dayStart, dayEnd].
                        // The condition is: booking starts before day ends AND booking ends after day starts.
                        if ($rawIn < $dayEnd && $rawOut > $dayStart) {
                            // Hitung porsi (fraction) dari hari ini yang ditempati oleh segmen ini
                            // Re-define dayEnd to be exclusive for diff calculation (next day 00:00)
                            $dayEndExclusive = $dayStart->copy()->addDay();
                            $morningStart = $dayStart->copy()->addHours(12);
                            $morningEnd = $dayStart->copy()->addHours(18);
                            $afternoonStart = $dayStart->copy()->addHours(19);
                            $afternoonEnd = $dayStart->copy()->addHours(24); // End of day (exclusive)
                            
                            $segStart = $rawIn->greaterThan($dayStart) ? $rawIn : $dayStart;
                            $segEnd = $rawOut->lessThan($dayEndExclusive) ? $rawOut : $dayEndExclusive;
                            $duration = max(0, $segEnd->diffInSeconds($segStart));
                            $fraction = $duration > 0 ? min(1, $duration / 86400) : 0;

                            $segments[] = [
                                'booking_order_id' => $row['booking_order_id'],
                                'booking_code' => $row['order_code'] ?? null,
                                'booking_code_short' => $row['order_code_short'] ?? null,
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
                            $noonToNextDayOrMore = ($rawIn->equalTo($afternoonStart) && $rawOut->gte($afternoonStart->copy()->addDay()));
                            if ($noonToNextDayOrMore) {
                                $slotMorning[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'booking_code_short' => $row['order_code_short'] ?? null,
                                    'status' => $row['status'],
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                ];
                                $slotAfternoon[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'booking_code_short' => $row['order_code_short'] ?? null,
                                    'status' => $row['status'],
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                ];
                            } else {
                                // Check for morning slot overlap (06:00 - 12:00)
                                if ($segEnd > $morningStart && $segStart < $morningEnd) {
                                    $slotMorning[] = [
                                        'booking_order_id' => $row['booking_order_id'],
                                        'booking_code' => $row['order_code'] ?? null,
                                        'booking_code_short' => $row['order_code_short'] ?? null,
                                        'status' => $row['status'],
                                        'payment' => $row['meta']['payment'] ?? null,
                                        'background' => $row['meta']['background'] ?? null,
                                        'text_color' => $row['meta']['text_color'] ?? null,
                                    ];
                                }
                                // Check for afternoon slot overlap (12:00 - 24:00)
                                if ($segEnd > $afternoonStart && $segStart < $afternoonEnd) {
                                    $slotAfternoon[] = [
                                        'booking_order_id' => $row['booking_order_id'],
                                        'booking_code' => $row['order_code'] ?? null,
                                        'booking_code_short' => $row['order_code_short'] ?? null,
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
                    'is_multi_day' => count($segments) > 0 && isset($segments[0]) && $segments[0]['checkout_at']->gt($carbonDate->copy()->addDay()), // Flag if this is a multi-day booking
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
