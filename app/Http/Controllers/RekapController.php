<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use App\Models\CafeOrder;
use Carbon\Carbon;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        // Rekap kamar: gunakan total_harga dari BookingOrder yang overlap bulan ini dan tidak dibatalkan
        $roomOrders = BookingOrder::whereIn('status', [1,2,3])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                         ->where('tanggal_checkout','>=',$end);
                  });
            })
            ->get();

        // Asumsi: total_harga sudah berisi total biaya kamar untuk order tsb
        $totalKamar = $roomOrders->sum(function($o){ return (float)($o->total_harga ?? 0); });

        // Tambahan: jika payment_status 'dp', tetap masukkan nominal penuh (asumsi rekap target pendapatan, bukan kas masuk)
        // Jika mau hanya yang sudah lunas, filter $roomOrders->where('payment_status','lunas')

        // Rekap Cafe: berdasarkan CafeOrder yang terkait booking dan dibuat pada bulan tsb.
        $cafeTotal = CafeOrder::whereBetween('created_at', [$start,$end])->sum('total');

        $grandTotal = $totalKamar + $cafeTotal;

        // Kirim ke view
        return view('rekap', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'totalKamar' => $totalKamar,
            'totalCafe' => $cafeTotal,
            'grandTotal' => $grandTotal,
            'start' => $start,
            'end' => $end,
            'orders' => $roomOrders,
        ]);
    }

    public function print(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $roomOrders = BookingOrder::whereIn('status', [1,2,3])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                         ->where('tanggal_checkout','>=',$end);
                  });
            })
            ->get();

        $totalKamar = $roomOrders->sum(function($o){ return (float)($o->total_harga ?? 0); });
        $cafeTotal = \App\Models\CafeOrder::whereBetween('created_at', [$start,$end])->sum('total');
        $grandTotal = $totalKamar + $cafeTotal;

        return view('rekap_print', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'totalKamar' => $totalKamar,
            'totalCafe' => $cafeTotal,
            'grandTotal' => $grandTotal,
            'start' => $start,
            'end' => $end,
            'orders' => $roomOrders,
            'printedAt' => Carbon::now(),
        ]);
    }
}
