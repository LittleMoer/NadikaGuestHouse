<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use App\Models\CafeOrder;
use Carbon\Carbon;

class RekapController extends Controller
{
    /**
     * Generate rekap secara real-time dari data booking dan cafe orders
     * Tidak lagi bergantung pada cash_ledger yang tersimpan
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        // Filters
        $paymentMethod = $request->get('payment_method'); // null|'cash'|'transfer'|'qris'|'card'
        $channel = $request->get('channel'); // null|'walkin'|'traveloka'|'agent1'|'agent2'
        $discount = $request->get('discount'); // null|'with'|'without'
        $paymentStatus = $request->get('payment_status'); // null|'dp'|'lunas'|'dp_cancel'

        // Generate entries dari BookingOrder secara real-time
        $bookingQuery = BookingOrder::with(['pelanggan', 'items.kamar'])
            ->whereIn('status', [1, 2, 3]) // Exclude cancelled (4)
            ->where(function($q) use ($start, $end) {
                // Booking yang dibuat dalam periode ini ATAU check-in/out dalam periode
                $q->whereBetween('created_at', [$start, $end])
                  ->orWhereBetween('tanggal_checkin', [$start, $end])
                  ->orWhereBetween('tanggal_checkout', [$start, $end]);
            });

        // Apply filters
        if ($paymentMethod && strtolower($paymentMethod) !== 'all') {
            $bookingQuery->where('payment_method', strtolower($paymentMethod));
        }
        if ($channel && strtolower($channel) !== 'all') {
            $map = ['walkin'=>0,'traveloka'=>1,'agent1'=>2,'agent2'=>3];
            if (isset($map[strtolower($channel)])) {
                $bookingQuery->where('pemesanan', $map[strtolower($channel)]);
            }
        }
        if ($paymentStatus && strtolower($paymentStatus) !== 'all') {
            $bookingQuery->where('payment_status', strtolower($paymentStatus));
        }
        if ($discount === 'with') {
            $bookingQuery->where(function($q){
                $q->where('diskon', '>', 0)
                  ->orWhere('discount_review', true)
                  ->orWhere('discount_follow', true);
            });
        } elseif ($discount === 'without') {
            $bookingQuery->where(function($q){
                $q->where(function($qq){ $qq->whereNull('diskon')->orWhere('diskon','=',0); })
                  ->where(function($qq){ $qq->whereNull('discount_review')->orWhere('discount_review', false); })
                  ->where(function($qq){ $qq->whereNull('discount_follow')->orWhere('discount_follow', false); });
            });
        }

        $bookings = $bookingQuery->orderBy('created_at', 'desc')->get();

        // Transform bookings menjadi entries
        $entries = collect();
        $totalKamar = 0;
        $totalCafe = 0;
        $totalBiayaTambahan = 0;

        foreach ($bookings as $booking) {
            $roomNumbers = $booking->items->pluck('kamar.nomor_kamar')->filter()->unique()->sort()->implode(', ');
            
            // Entry untuk room payment
            $roomTotal = (int)($booking->total_harga ?? 0);
            $biayaTambahan = (int)($booking->biaya_tambahan ?? 0);
            $dpAmount = (int)($booking->dp_amount ?? 0);
            $paymentStatusLabel = $booking->payment_status ?? 'dp';
            
            // Tentukan nominal yang masuk berdasarkan payment status
            if ($paymentStatusLabel === 'lunas') {
                $nominalMasuk = $roomTotal + $biayaTambahan;
                $keterangan = 'Pembayaran Lunas - Kamar';
            } elseif ($paymentStatusLabel === 'dp') {
                $nominalMasuk = $dpAmount;
                $keterangan = 'DP - Kamar';
            } else {
                $nominalMasuk = 0;
                $keterangan = 'Pembayaran Dibatalkan';
            }

            if ($nominalMasuk > 0) {
                $entries->push((object)[
                    'ledger_id' => 'booking_' . $booking->id,
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'pelanggan_nama' => $booking->pelanggan->nama ?? '-',
                    'payment_method' => $booking->payment_method,
                    'pemesanan' => $booking->pemesanan,
                    'type' => $paymentStatusLabel === 'lunas' ? 'payment_lunas' : 'dp_in',
                    'note' => $keterangan,
                    'amount' => $nominalMasuk,
                    'display_amount' => $nominalMasuk,
                    'created_at' => $booking->created_at,
                    'booking_created_at' => $booking->created_at,
                    'tanggal_checkin' => $booking->tanggal_checkin,
                    'tanggal_checkout' => $booking->tanggal_checkout,
                    'room_numbers' => $roomNumbers,
                    'payment_status' => $paymentStatusLabel,
                ]);
                $totalKamar += $nominalMasuk;
            }

            // Entry untuk Cafe jika ada
            $cafeTotal = (int)($booking->total_cafe ?? 0);
            if ($cafeTotal > 0) {
                $entries->push((object)[
                    'ledger_id' => 'cafe_' . $booking->id,
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'pelanggan_nama' => $booking->pelanggan->nama ?? '-',
                    'payment_method' => $booking->payment_method,
                    'pemesanan' => $booking->pemesanan,
                    'type' => 'cafe_in',
                    'note' => 'Pesanan Cafe',
                    'amount' => $cafeTotal,
                    'display_amount' => $cafeTotal,
                    'created_at' => $booking->created_at,
                    'booking_created_at' => $booking->created_at,
                    'tanggal_checkin' => $booking->tanggal_checkin,
                    'tanggal_checkout' => $booking->tanggal_checkout,
                    'room_numbers' => $roomNumbers,
                    'payment_status' => $paymentStatusLabel,
                ]);
                $totalCafe += $cafeTotal;
            }
        }

        // Sort entries by created_at
        $entries = $entries->sortByDesc('created_at')->values();

        // Calculate grand total
        $cashGrand = $totalKamar + $totalCafe;

        return view('rekap', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'start' => $start,
            'end' => $end,
            'entries' => $entries,
            'cashGrand' => $cashGrand,
            'totalKamar' => $totalKamar,
            'totalCafe' => $totalCafe,
            // expose filters to the view
            'filter_payment_method' => $paymentMethod,
            'filter_channel' => $channel,
            'filter_discount' => $discount,
            'filter_payment_status' => $paymentStatus,
        ]);
    }

    public function destroy(Request $request, $ledgerId)
    {
        // Untuk sistem baru, kita tidak menghapus dari ledger
        // Karena data di-generate real-time dari booking
        // Jadi kita perlu cancel/update booking itu sendiri
        
        // Parse ledger_id: format "booking_123" atau "cafe_123"
        if (str_starts_with($ledgerId, 'booking_') || str_starts_with($ledgerId, 'cafe_')) {
            $bookingId = (int) str_replace(['booking_', 'cafe_'], '', $ledgerId);
            $booking = BookingOrder::find($bookingId);
            
            if ($booking) {
                // Option 1: Set payment status to cancelled
                $booking->payment_status = 'dp_cancel';
                $booking->save();
                
                return redirect()->route('rekap.index', [
                    'bulan' => $request->get('bulan'),
                    'tahun' => $request->get('tahun'),
                ])->with('success', 'Status pembayaran booking diubah menjadi dibatalkan');
            }
        }
        
        // Fallback: tetap support old cash_ledger deletion
        \DB::table('cash_ledger')->where('id', $ledgerId)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
        
        if($request->wantsJson()){
            return response()->json(['success'=>true]);
        }
        return redirect()->route('rekap.index', [
            'bulan' => $request->get('bulan'),
            'tahun' => $request->get('tahun'),
        ])->with('success','Baris rekap dihapus');
    }

    public function print(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        // Get bookings untuk periode ini
        $roomOrders = BookingOrder::with(['pelanggan', 'items.kamar'])
            ->whereIn('status', [1,2,3])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('created_at', [$start, $end])
                  ->orWhereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end]);
            })
            ->get();

        // Hitung total secara real-time dari booking orders
        $totalKamar = 0;
        $totalCafe = 0;
        $totalDpIn = 0;
        $totalLunas = 0;

        foreach ($roomOrders as $order) {
            $paymentStatus = $order->payment_status ?? 'dp';
            $roomTotal = (int)($order->total_harga ?? 0);
            $biayaTambahan = (int)($order->biaya_tambahan ?? 0);
            $dpAmount = (int)($order->dp_amount ?? 0);
            $cafeTotal = (int)($order->total_cafe ?? 0);

            if ($paymentStatus === 'lunas') {
                $totalLunas += ($roomTotal + $biayaTambahan);
                $totalKamar += ($roomTotal + $biayaTambahan);
            } elseif ($paymentStatus === 'dp') {
                $totalDpIn += $dpAmount;
                $totalKamar += $dpAmount;
            }

            $totalCafe += $cafeTotal;
        }

        $grandTotal = $totalKamar + $totalCafe;
        $cashGrand = $grandTotal;

        return view('rekap_print', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'totalDpIn' => $totalDpIn,
            'totalDpRemaining' => $totalLunas,
            'totalDpCanceled' => 0,
            'totalCafeIn' => $totalCafe,
            'totalCashback' => 0,
            'cashGrand' => $cashGrand,
            'totalKamar' => $totalKamar,
            'totalCafe' => $totalCafe,
            'grandTotal' => $grandTotal,
            'start' => $start,
            'end' => $end,
            'orders' => $roomOrders,
            'printedAt' => Carbon::now(),
        ]);
    }
}
