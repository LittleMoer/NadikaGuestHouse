<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kamar;
use App\Models\BookingOrder;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $kamarList = Kamar::all();
        $jenisKamar = $kamarList->pluck('tipe')->unique()->values();

        $preferred = ['family','superior','twin','standar','standar eco'];
        $prefMap = collect($preferred)->mapWithKeys(fn($v,$i)=> [strtolower(trim($v))=>$i+1])->all();

        $orderedJenisKamar = $jenisKamar->sortBy(function($t) use ($prefMap){
            $key = strtolower(trim((string)$t));
            if ($key === 'non ac') return sprintf('%06d-%s', 999998, $key);
            if ($key === 'hall')   return sprintf('%06d-%s', 999999, $key);
            if (array_key_exists($key, $prefMap))
                return sprintf('%06d-%s', $prefMap[$key], $key);
            return sprintf('%06d-%s', 900000, $key);
        })->values();

        $kamarGrouped = $kamarList->groupBy('tipe')->map(fn($group) => 
            $group->sortBy('nomor_kamar', SORT_NATURAL)->values()
        );

        $activeOrders = BookingOrder::with(['items' => fn($q) => $q->with('kamar'), 'pelanggan'])
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

        $items = [];
        foreach($activeOrders as $order){
            $meta = $order->status_meta;
            $ciAt = Carbon::parse($order->tanggal_checkin);
            $coAt = Carbon::parse($order->tanggal_checkout);

            foreach($order->items as $it){
                $code = (string)($order->order_code ?? '');
                $short = strlen($code) >= 3 ? substr($code, -3) : $code;
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status,
                    'booking_order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_code_short' => $short,
                    'meta' => $meta,
                    'checkin_at' => $ciAt,
                    'checkout_at' => $coAt,
                ];
            }
        }

        $itemsByKamar = collect($items)->groupBy('kamar_id');

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

                if(isset($itemsByKamar[$kamar->id])){
                    foreach($itemsByKamar[$kamar->id] as $row){
                        $rawIn = $row['checkin_at'];
                        $rawOut = $row['checkout_at'];

                        $dayStart = $carbonDate->copy()->startOfDay();
                        $dayEnd = $carbonDate->copy()->endOfDay();

                        if ($rawIn < $dayEnd && $rawOut > $dayStart) {
                            $segments[] = [
                                'booking_order_id' => $row['booking_order_id'],
                                'booking_code' => $row['order_code'],
                                'booking_code_short' => $row['order_code_short'],
                                'status' => $row['status'],
                                'payment' => $row['meta']['payment'] ?? null,
                                'channel' => $row['meta']['channel'] ?? null,
                                'background' => $row['meta']['background'] ?? null,
                                'text_color' => $row['meta']['text_color'] ?? null,
                                'checkin_at' => $rawIn,
                                'checkout_at' => $rawOut,
                            ];

                            $isCheckinDay = $rawIn->isSameDay($carbonDate);
                            $isCheckoutDay = $rawOut->isSameDay($carbonDate);
                            $startsAtNoon = $rawIn->format('H:i:s') === '12:00:00';
                            $startsAtMidnight = $rawIn->format('H:i:s') === '00:00:00';

                            // Logika utama
                            if ($isCheckinDay && $startsAtNoon) {
                                // Check-in jam 12:00 isi slot sore
                                $slotAfternoon[] = $this->buildSlot($row);
                            } elseif ($isCheckinDay && $startsAtMidnight) {
                                // Check-in jam 00:00 isi slot pagi
                                $slotMorning[] = $this->buildSlot($row);
                            } elseif ($carbonDate->gt($rawIn->copy()->startOfDay()) && $carbonDate->lt($rawOut->copy()->startOfDay())) {
                                // Hari di antara checkin dan checkout: isi penuh
                                $slotMorning[] = $this->buildSlot($row);
                                $slotAfternoon[] = $this->buildSlot($row);
                            } elseif ($isCheckoutDay) {
                                // Hari checkout: pagi saja
                                $slotMorning[] = $this->buildSlot($row);
                            }

                            if($row['status'] == 2) $isOccupied = true;
                        }
                    }
                }

                usort($segments, fn($a,$b) => $a['checkin_at'] <=> $b['checkin_at']);

                $statusBooking[$tgl][$kamar->id] = [
                    'segments' => $segments,
                    'occ' => $isOccupied ? 'occupied' : (count($segments) ? 'booked' : 'empty'),
                    'slot_morning' => $slotMorning,
                    'slot_afternoon' => $slotAfternoon,
                    'is_multi_day' => count($segments) > 0 && isset($segments[0]) && $segments[0]['checkout_at']->gt($carbonDate->copy()->addDay()),
                ];

                $hasMorning = !empty($slotMorning);
                $hasAfternoon = !empty($slotAfternoon);
                if($hasMorning || $hasAfternoon){
                    $totalKamarTerisiBulan++;
                }
            }
        }

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

    private function buildSlot($row)
    {
        return [
            'booking_order_id' => $row['booking_order_id'],
            'booking_code' => $row['order_code'],
            'booking_code_short' => $row['order_code_short'],
            'status' => $row['status'],
            'payment' => $row['meta']['payment'] ?? null,
            'background' => $row['meta']['background'] ?? null,
            'text_color' => $row['meta']['text_color'] ?? null,
        ];
    }
}
