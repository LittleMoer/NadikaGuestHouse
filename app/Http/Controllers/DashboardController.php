<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Kamar;
use App\Models\BookingOrder;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $bulan = $request->get('bulan', $now->month);
        $tahun = $request->get('tahun', $now->year);

        $tanggalAwal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        $kamars = Kamar::orderBy('jenis')->orderBy('kode')->get();

        $bookings = BookingOrder::with(['details.kamar'])
            ->whereBetween('tanggal_checkin', [$tanggalAwal, $tanggalAkhir])
            ->orWhereBetween('tanggal_checkout', [$tanggalAwal, $tanggalAkhir])
            ->get();

        $items = [];
        foreach ($bookings as $booking) {
            foreach ($booking->details as $detail) {
                $in = Carbon::parse($booking->tanggal_checkin);
                $out = Carbon::parse($booking->tanggal_checkout);

                $items[] = [
                    'booking_order_id' => $booking->id,
                    'kamar_id' => $detail->kamar_id,
                    'order_code' => $booking->kode,
                    'order_code_short' => substr($booking->kode, -3),
                    'status' => $booking->status,
                    'meta' => [
                        'background' => $booking->meta_background ?? '#f87171',
                        'text_color' => '#fff',
                        'payment' => $booking->payment_status,
                    ],
                    'checkin' => $in,
                    'checkout' => $out,
                ];
            }
        }

        $daysInMonth = [];
        for ($d = 0; $d < $tanggalAkhir->day; $d++) {
            $daysInMonth[] = $tanggalAwal->copy()->addDays($d);
        }

        $itemsByKamar = [];
        foreach ($kamars as $kamar) {
            $itemsByKamar[$kamar->id] = [];

            foreach ($daysInMonth as $carbonDate) {
                $slotMorning = [];
                $slotAfternoon = [];

                $morningStart = $carbonDate->copy()->setTime(6, 0, 0);
                $morningEnd = $carbonDate->copy()->setTime(11, 59, 59);
                $afternoonStart = $carbonDate->copy()->setTime(12, 0, 0);
                $afternoonEnd = $carbonDate->copy()->setTime(23, 59, 59);

                foreach ($items as $row) {
                    if ($row['kamar_id'] != $kamar->id) continue;

                    $rawIn = $row['checkin'];
                    $rawOut = $row['checkout'];
                    $segStart = $rawIn->copy();
                    $segEnd = $rawOut->copy();

                    $isCheckinDay = $rawIn->isSameDay($carbonDate);
                    $startsAtNoon = $rawIn->format('H:i:s') === '12:00:00';
                    $startsAtMidnight = $rawIn->format('H:i:s') === '00:00:00';

                    // Hari pertama booking
                    if ($isCheckinDay) {
                        if ($startsAtNoon) {
                            // Mulai siang → isi slot sore
                            $slotAfternoon[] = $this->bookingSlot($row);
                        } elseif ($startsAtMidnight) {
                            // Mulai tengah malam → isi slot pagi
                            $slotMorning[] = $this->bookingSlot($row);
                        } else {
                            // Default: isi pagi & sore hari pertama
                            $slotMorning[] = $this->bookingSlot($row);
                            $slotAfternoon[] = $this->bookingSlot($row);
                        }
                        continue;
                    }

                    // Hari di antara checkin dan checkout → isi pagi & sore
                    if ($carbonDate->between($rawIn->copy()->addDay(), $rawOut->copy()->subDay(), true)) {
                        $slotMorning[] = $this->bookingSlot($row);
                        $slotAfternoon[] = $this->bookingSlot($row);
                        continue;
                    }

                    // Hari checkout → hanya pagi
                    if ($rawOut->isSameDay($carbonDate)) {
                        $slotMorning[] = $this->bookingSlot($row);
                    }
                }

                $itemsByKamar[$kamar->id][$carbonDate->format('Y-m-d')] = [
                    'morning' => $slotMorning,
                    'afternoon' => $slotAfternoon,
                ];
            }
        }

        return view('admin.dashboard.index', [
            'kamars' => $kamars,
            'daysInMonth' => $daysInMonth,
            'itemsByKamar' => $itemsByKamar,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    private function bookingSlot($row)
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
