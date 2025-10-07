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

        // Cash-in log: dp_in, dp_remaining_in, cafe_in (exclude dp_canceled)
        $entries = \DB::table('cash_ledger as l')
            ->leftJoin('booking as b', 'b.id', '=', 'l.booking_id')
            ->leftJoin('pelanggan as p', 'p.id', '=', 'b.pelanggan_id')
            ->leftJoin('booking_order_items as boi', 'boi.booking_order_id', '=', 'l.booking_id')
            ->leftJoin('kamar as k', 'k.id', '=', 'boi.kamar_id')
            ->whereBetween('l.created_at', [$start, $end])
            ->whereIn('l.type', ['dp_in','dp_remaining_in','cafe_in'])
            ->orderBy('l.created_at','asc')
            ->groupBy(
                'l.id','l.booking_id','p.nama','b.payment_method','b.pemesanan','l.type','l.note','l.amount','l.created_at',
                'b.created_at','b.tanggal_checkin','b.tanggal_checkout'
            )
            ->select([
                'l.id as ledger_id',
                'l.booking_id',
                'p.nama as pelanggan_nama',
                'b.payment_method',
                'b.pemesanan',
                'l.type',
                'l.note',
                'l.amount',
                'l.created_at',
                'b.created_at as booking_created_at',
                'b.tanggal_checkin',
                'b.tanggal_checkout',
                \DB::raw("GROUP_CONCAT(DISTINCT k.nomor_kamar ORDER BY k.nomor_kamar SEPARATOR ', ') as room_numbers")
            ])
            ->get();

        $cashGrand = (int) \DB::table('cash_ledger')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('type', ['dp_in','dp_remaining_in','cafe_in'])
            ->sum('amount');

        return view('rekap', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'start' => $start,
            'end' => $end,
            'entries' => $entries,
            'cashGrand' => (int)$cashGrand,
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

        $ledger = \DB::table('cash_ledger')
            ->select('type', \DB::raw('SUM(amount) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('type')
            ->pluck('total','type');
        $totalDpIn = (int)($ledger['dp_in'] ?? 0);
        $totalDpRemaining = (int)($ledger['dp_remaining_in'] ?? 0);
        $totalDpCanceled = (int)($ledger['dp_canceled'] ?? 0);
        $totalCafeIn = (int)($ledger['cafe_in'] ?? 0);
        $cashGrand = $totalDpIn + $totalDpRemaining + $totalCafeIn;

        return view('rekap_print', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'totalDpIn' => $totalDpIn,
            'totalDpRemaining' => $totalDpRemaining,
            'totalDpCanceled' => $totalDpCanceled,
            'totalCafeIn' => $totalCafeIn,
            'cashGrand' => $cashGrand,
            'start' => $start,
            'end' => $end,
            'orders' => $roomOrders,
            'printedAt' => Carbon::now(),
        ]);
    }
}
