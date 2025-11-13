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

        // Ambil semua kamar
        $kamarList = Kamar::all();
        $jenisKamar = $kamarList->pluck('tipe')->unique()->values();

        // Urutan custom tipe kamar
        $preferred = ['family', 'superior', 'twin', 'standar', 'standar eco'];
        $prefMap = collect($preferred)->mapWithKeys(fn($v, $i) => [strtolower(trim($v)) => $i + 1])->all();
        $orderedJenisKamar = $jenisKamar->sortBy(function ($t) use ($prefMap) {
            $key = strtolower(trim((string) $t));
            if ($key === 'non ac')
                return sprintf('%06d-%s', 999998, $key);
            if ($key === 'hall')
                return sprintf('%06d-%s', 999999, $key);
            if (array_key_exists($key, $prefMap)) {
                return sprintf('%06d-%s', $prefMap[$key], $key);
            }
            return sprintf('%06d-%s', 900000, $key);
        })->values();

        $kamarGrouped = $kamarList->groupBy('tipe')->map(function ($group) {
            return $group->sortBy('nomor_kamar', SORT_NATURAL)->values();
        });

        // Ambil booking aktif di bulan ini
        $activeOrders = BookingOrder::with(['items' => function ($q) {
            $q->with('kamar'); }, 'pelanggan'])
            ->whereIn('status', [1, 2, 3, 4])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal_checkin', [$start, $end])
                    ->orWhereBetween('tanggal_checkout', [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where('tanggal_checkin', '<=', $start)
                            ->where('tanggal_checkout', '>=', $end);
                    });
            })
            ->get();

        // Normalisasi items
        $items = [];
        foreach ($activeOrders as $order) {
            $meta = $order->status_meta;
            $ciDay = Carbon::parse($order->tanggal_checkin)->startOfDay();
            $coDay = Carbon::parse($order->tanggal_checkout)->startOfDay();
            $ciAt = Carbon::parse($order->tanggal_checkin);
            $coAt = Carbon::parse($order->tanggal_checkout);
            $coDay_endExclusive = $coDay->copy();
            if ($coAt->gt($coDay))
                $coDay_endExclusive = $coDay->copy()->addDay();
            $coDay = $coDay_endExclusive->copy()->subDay();

            foreach ($order->items as $it) {
                $code = (string) ($order->order_code ?? '');
                $short = strlen($code) >= 3 ? substr($code, -3) : $code;
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status,
                    'booking_order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_code_short' => $short,
                    'meta' => $meta,
                    'checkin' => $ciDay,
                    'checkout' => $coDay,
                    'checkin_at' => $ciAt,
                    'checkout_at' => $coAt,
                ];
            }
        }

        $itemsByKamar = collect($items)->groupBy('kamar_id');

        // Daftar tanggal bulan ini
        $tanggalList = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $tanggalList[] = $cursor->format('Y-m-d');
        }

        $statusBooking = [];
        $totalKamarTerisiBulan = 0;

        foreach ($tanggalList as $tgl) {
            $carbonDate = Carbon::parse($tgl);
            foreach ($kamarList as $kamar) {
                $segments = [];
                $slotMorning = [];
                $slotAfternoon = [];
                $isOccupied = false;

                if (isset($itemsByKamar[$kamar->id])) {
                    foreach ($itemsByKamar[$kamar->id] as $row) {
                        $rawIn = $row['checkin_at'];
                        $rawOut = $row['checkout_at'];
                        $dayStart = $carbonDate->copy()->startOfDay();
                        $dayEnd = $carbonDate->copy()->endOfDay();

                        if ($rawIn < $dayEnd && $rawOut > $dayStart) {
                            $dayEndExclusive = $dayStart->copy()->addDay();
                            $morningStart = $dayStart->copy()->addHours(6);
                            $morningEnd = $dayStart->copy()->addHours(12);
                            $afternoonStart = $dayStart->copy()->addHours(12);
                            $afternoonEnd = $dayStart->copy()->addHours(24);

                            $segStart = $rawIn->greaterThan($dayStart) ? $rawIn : $dayStart;
                            $segEnd = $rawOut->lessThan($dayEndExclusive) ? $rawOut : $dayEndExclusive;
                            $duration = max(0, $segEnd->diffInSeconds($segStart));
                            $fraction = $duration > 0 ? min(1, $duration / 86400) : 0;

                            $segments[] = [
                                'booking_order_id' => $row['booking_order_id'],
                                'booking_code' => $row['order_code'] ?? null,
                                'booking_code_short' => $row['order_code_short'] ?? null,
                                'status' => $row['status'],
                                'payment' => $row['meta']['payment'] ?? null,
                                'channel' => $row['meta']['channel'] ?? null,
                                'background' => $row['meta']['background'] ?? null,
                                'text_color' => $row['meta']['text_color'] ?? null,
                                'checkin_at' => $row['checkin_at'],
                                'checkout_at' => $row['checkout_at'],
                                'fraction' => $fraction,
                            ];

                            // === Logic slot diperbarui ===
                            $isCheckinDay = $rawIn->isSameDay($carbonDate);
                            $isCheckoutDay = $rawOut->isSameDay($carbonDate);
                            $startsAtNoon = $rawIn->format('H:i:s') === '12:00:00';
                            $startsAtMidnight = $rawIn->format('H:i:s') === '00:00:00';

                            // Hari check-in
                            if ($isCheckinDay && $startsAtNoon) {
                                // Hari pertama isi slot sore
                                $slotAfternoon[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'booking_code_short' => $row['order_code_short'] ?? null,
                                    'status' => $row['status'],
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                ];
                            } elseif ($isCheckinDay && $startsAtMidnight) {
                                // Hari pertama jam 00:00 isi slot pagi
                                $slotMorning[] = [
                                    'booking_order_id' => $row['booking_order_id'],
                                    'booking_code' => $row['order_code'] ?? null,
                                    'booking_code_short' => $row['order_code_short'] ?? null,
                                    'status' => $row['status'],
                                    'payment' => $row['meta']['payment'] ?? null,
                                    'background' => $row['meta']['background'] ?? null,
                                    'text_color' => $row['meta']['text_color'] ?? null,
                                ];
                            } else {
                                // Hari-hari di antara checkin dan checkout (inap tengah)
                                if ($carbonDate->gt($rawIn->copy()->startOfDay()) && $carbonDate->lt($rawOut->copy()->startOfDay())) {
                                    // Hari penuh di tengah periode
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
                                } elseif ($isCheckoutDay) {
                                    // Hari checkout hanya isi pagi (sebelum jam 12)
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
                            }
                            if ($row['status'] == 2)
                                $isOccupied = true;
                        }
                    }
                }

                usort($segments, function ($a, $b) {
                    return $a['checkin_at'] <=> $b['checkin_at'];
                });

                $statusBooking[$tgl][$kamar->id] = [
                    'segments' => $segments,
                    'occ' => $isOccupied ? 'occupied' : (count($segments) ? 'booked' : 'empty'),
                    'slot_morning' => $slotMorning,
                    'slot_afternoon' => $slotAfternoon,
                    'is_multi_day' => count($segments) > 0 && isset($segments[0]) && $segments[0]['checkout_at']->gt($carbonDate->copy()->addDay()),
                ];

                $dayStart = $carbonDate->copy()->startOfDay();
                $noon = $dayStart->copy()->addHours(12);
                $dayEnd = $dayStart->copy()->addDay();
                $hasMorning = !empty($slotMorning);
                $hasAfternoon = !empty($slotAfternoon);
                $coversNoon = false;
                foreach ($segments as $sg) {
                    $s = $sg['checkin_at'] ?? $dayStart;
                    $e = $sg['checkout_at'] ?? $dayEnd;
                    if ($s < $noon && $e > $noon) {
                        $coversNoon = true;
                        break;
                    }
                }
                if ($coversNoon || $hasMorning || $hasAfternoon) {
                    $totalKamarTerisiBulan++;
                }
            }
        }

        $prevMonth = $bulan - 1 < 1 ? 12 : $bulan - 1;
        $prevYear = $bulan - 1 < 1 ? $tahun - 1 : $tahun;
        $nextMonth = $bulan + 1 > 12 ? 1 : $bulan + 1;
        $nextYear = $bulan + 1 > 12 ? $tahun + 1 : $tahun;

        return view('dashboard', compact(
            'bulan',
            'tahun',
            'prevMonth',
            'prevYear',
            'nextMonth',
            'nextYear',
            'kamarList',
            'jenisKamar',
            'orderedJenisKamar',
            'kamarGrouped',
            'tanggalList',
            'statusBooking',
            'totalKamarTerisiBulan'
        ));
    }
}
