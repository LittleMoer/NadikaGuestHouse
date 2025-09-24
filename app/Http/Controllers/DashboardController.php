<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kamar;
use App\Models\Booking;
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

        // Ambil booking yang overlap bulan ini (status aktif 1=dipesan,2=checkin)
        $bookings = Booking::whereIn('status', [1,2])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                         ->where('tanggal_checkout','>=',$end);
                  });
            })
            ->get()
            ->groupBy('kamar_id');

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
                if (isset($bookings[$kamar->id])) {
                    foreach ($bookings[$kamar->id] as $b) {
                        // Hitung interval malam: include checkin day, exclude checkout day
                        $checkin = Carbon::parse($b->tanggal_checkin)->startOfDay();
                        $checkout = Carbon::parse($b->tanggal_checkout)->startOfDay();
                        if ($carbonDate->gte($checkin) && $carbonDate->lt($checkout)) {
                            if ($b->status == 2) {
                                $status = 'ditempati';
                                $bookingIdForCell = $b->id;
                                $totalKamarTerisiBulan++;
                                break; // occupied priority
                            } elseif ($b->status == 1 && $status !== 'ditempati') {
                                $status = 'dipesan';
                                $bookingIdForCell = $b->id;
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
