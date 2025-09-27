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
        $kamarList = Kamar::orderBy('nomor_kamar')->get();
        $jenisKamar = $kamarList->pluck('tipe')->unique()->values();
        // Urutan tipe yang diinginkan (Standard dulu agar sesuai ekspektasi penomoran)
        $preferredOrder = ['Standard','Deluxe','Suite'];
        $orderMap = collect($preferredOrder)->flip();
        $orderedJenisKamar = $jenisKamar->sortBy(fn($t) => $orderMap[$t] ?? 999)->values();
        // Kamar digrup per tipe dan diurutkan sesuai peta
        $kamarGrouped = $kamarList->groupBy('tipe')->map(function($group){
            return $group->sortBy('nomor_kamar')->values();
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
            if ($coDay->equalTo($ciDay)) {
                $coDay = $coDay->copy()->addDay();
            }
            $ciAt = Carbon::parse($order->tanggal_checkin);
            $coAt = Carbon::parse($order->tanggal_checkout);
            foreach($order->items as $it){
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status, // 1..4
                    'booking_order_id' => $order->id,
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
                $isOccupied = false;
                if(isset($itemsByKamar[$kamar->id])){
                    foreach($itemsByKamar[$kamar->id] as $row){
                        if($carbonDate->gte($row['checkin']) && $carbonDate->lt($row['checkout'])){
                            // Hitung porsi (fraction) dari hari ini yang ditempati oleh segmen ini
                            $dayStart = $carbonDate->copy()->startOfDay();
                            $dayEnd = $dayStart->copy()->addDay();
                            $segStart = $row['checkin_at']->greaterThan($dayStart) ? $row['checkin_at'] : $dayStart;
                            $segEnd = $row['checkout_at']->lessThan($dayEnd) ? $row['checkout_at'] : $dayEnd;
                            $duration = max(0, $segEnd->diffInSeconds($segStart));
                            $fraction = $duration > 0 ? min(1, $duration / 86400) : 0;

                            $segments[] = [
                                'booking_order_id' => $row['booking_order_id'],
                                'status' => $row['status'], // 1..4
                                'payment' => $row['meta']['payment'] ?? null,
                                'channel' => $row['meta']['channel'] ?? null,
                                'background' => $row['meta']['background'] ?? null,
                                'text_color' => $row['meta']['text_color'] ?? null,
                                'checkin_at' => $row['checkin_at'],
                                'checkout_at' => $row['checkout_at'],
                                'fraction' => $fraction,
                            ];
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
                ];
                if($isOccupied) $totalKamarTerisiBulan++;
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
