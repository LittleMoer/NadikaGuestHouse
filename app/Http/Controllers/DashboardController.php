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

        // Ambil booking orders aktif (status 1=dipesan,2=checkin) beserta items yang overlap bulan ini
        $activeOrders = BookingOrder::with(['items' => function($q){ $q->with('kamar'); }, 'pelanggan'])
            ->whereIn('status',[1,2])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                         ->where('tanggal_checkout','>=',$end);
                  });
            })
            ->get();

        // Flatten items with reference to parent order status & dates
        $items = [];
        foreach($activeOrders as $order){
            foreach($order->items as $it){
                $items[] = [
                    'kamar_id' => $it->kamar_id,
                    'status' => $order->status, // 1/2
                    'booking_order_id' => $order->id,
                    'checkin' => Carbon::parse($order->tanggal_checkin)->startOfDay(),
                    'checkout' => Carbon::parse($order->tanggal_checkout)->startOfDay(),
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
                $status = 'kosong';
                $bookingIdForCell = null;
                if(isset($itemsByKamar[$kamar->id])){
                    foreach($itemsByKamar[$kamar->id] as $row){
                        if($carbonDate->gte($row['checkin']) && $carbonDate->lt($row['checkout'])){
                            if($row['status'] == 2){
                                $status = 'ditempati';
                                $bookingIdForCell = $row['booking_order_id'];
                                $totalKamarTerisiBulan++;
                                break; // prioritas checkin
                            } elseif($row['status'] == 1 && $status !== 'ditempati') {
                                $status = 'dipesan';
                                $bookingIdForCell = $row['booking_order_id'];
                            }
                        }
                    }
                }
                $statusBooking[$tgl][$kamar->id] = ['status'=>$status,'booking_id'=>$bookingIdForCell];
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
