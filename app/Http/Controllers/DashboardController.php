public function index(Request $request)
{
    $now = Carbon::now();
    $month = $request->get('month', $now->format('Y-m'));
    $carbonMonth = Carbon::parse($month . '-01');
    $daysInMonth = $carbonMonth->daysInMonth;

    $tanggalMulai = $carbonMonth->copy()->startOfMonth();
    $tanggalSelesai = $carbonMonth->copy()->endOfMonth();

    $dataKamar = Kamar::with(['tipe'])
        ->orderBy('tipe_id')
        ->orderBy('nomor')
        ->get();

    $items = BookingKamar::query()
        ->with(['bookingOrder'])
        ->whereBetween('checkin', [$tanggalMulai, $tanggalSelesai->copy()->addDay()])
        ->orWhereBetween('checkout', [$tanggalMulai, $tanggalSelesai->copy()->addDay()])
        ->get();

    $itemsByKamar = [];
    foreach ($items as $item) {
        $itemsByKamar[$item->kamar_id][] = [
            'booking_order_id' => $item->booking_order_id,
            'order_code' => $item->bookingOrder->order_code ?? null,
            'order_code_short' => $item->bookingOrder->order_code_short ?? null,
            'checkin' => $item->checkin,
            'checkout' => $item->checkout,
            'status' => $item->status,
            'meta' => [
                'background' => $item->bookingOrder->background ?? null,
                'text_color' => $item->bookingOrder->text_color ?? null,
                'payment' => $item->bookingOrder->payment_status ?? null,
            ]
        ];
    }

    $listTanggal = [];
    $totalKamarTerisiBulan = 0;

    for ($i = 0; $i < $daysInMonth; $i++) {
        $carbonDate = $carbonMonth->copy()->addDays($i);
        $tanggalKey = $carbonDate->format('Y-m-d');

        $listTanggal[$tanggalKey] = [
            'tanggal' => $carbonDate->format('d'),
            'hari' => $carbonDate->format('D'),
            'kamar' => [],
        ];

        foreach ($dataKamar as $kamar) {
            $slotMorning = [];
            $slotAfternoon = [];
            $isOccupied = false;

            if (isset($itemsByKamar[$kamar->id])) {
                foreach ($itemsByKamar[$kamar->id] as $row) {
                    $rawIn = Carbon::parse($row['checkin']);
                    $rawOut = Carbon::parse($row['checkout']);

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

                    if ($row['status'] == 2) {
                        $isOccupied = true;
                    }
                }
            }

            $listTanggal[$tanggalKey]['kamar'][$kamar->id] = [
                'morning' => $slotMorning,
                'afternoon' => $slotAfternoon,
                'is_occupied' => $isOccupied,
            ];

            if ($isOccupied) {
                $totalKamarTerisiBulan++;
            }
        }
    }

    return view('admin.occupancy.index', compact(
        'listTanggal',
        'dataKamar',
        'month',
        'totalKamarTerisiBulan'
    ));
}
