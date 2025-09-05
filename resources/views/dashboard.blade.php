@extends('layouts.templateowner')

@section('dashboard')
    <div class="container">
      <div class="page-inner">
        <div class="page-header">
          <h1 class="h1" style="font-size:2rem;font-weight:bold;">Dashboard</h1>
        </div>
        <div class="page-title" style="font-size:1.2rem;font-weight:600;margin-bottom:1rem;">Selamat datang {{ Auth::user()->name }}</div>
        @php
            use Carbon\Carbon;
            $jenisKamar = ['Standard', 'Deluxe', 'Suite'];
            $kamarList = [];
            for ($i = 1; $i <= 17; $i++) {
                $kamarList[] = (object)[
                    'id' => $i,
                    'nomor_kamar' => str_pad($i, 3, '0', STR_PAD_LEFT),
                    'jenis' => $jenisKamar[($i-1)%3]
                ];
            }
            $bulan = request()->get('bulan', Carbon::now()->month);
            $tahun = request()->get('tahun', Carbon::now()->year);
            $start = Carbon::create($tahun, $bulan, 1);
            $end = $start->copy()->endOfMonth();
            $tanggalList = [];
            for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                $tanggalList[] = $date->format('Y-m-d');
            }
            $statusBooking = [];
            foreach ($tanggalList as $tanggal) {
                foreach ($kamarList as $kamar) {
                    $rand = rand(0,4);
                    $statusBooking[$tanggal][$kamar->id] = match($rand) {
                        0 => 'kosong',
                        1 => 'dipesan_langsung',
                        2 => 'dipesan_eticket',
                        3 => 'ditempati_langsung',
                        4 => 'ditempati_eticket',
                    };
                }
            }
            $prevMonth = $bulan - 1 < 1 ? 12 : $bulan - 1;
            $prevYear = $bulan - 1 < 1 ? $tahun - 1 : $tahun;
            $nextMonth = $bulan + 1 > 12 ? 1 : $bulan + 1;
            $nextYear = $bulan + 1 > 12 ? $tahun + 1 : $tahun;
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:2rem;margin-bottom:1rem;">
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <a href="?bulan={{ $prevMonth }}&tahun={{ $prevYear }}" style="padding:0.5rem 1rem;border-radius:0.5rem;background:#007bff;color:#fff;font-size:1.2rem;text-decoration:none;">&#8592;</a>
                <span style="font-size:1.2rem;font-weight:bold;color:#333;">{{ DateTime::createFromFormat('!m', $bulan)->format('F') }} {{ $tahun }}</span>
                <a href="?bulan={{ $nextMonth }}&tahun={{ $nextYear }}" style="padding:0.5rem 1rem;border-radius:0.5rem;background:#007bff;color:#fff;font-size:1.2rem;text-decoration:none;">&#8594;</a>
            </div>
            <form method="GET" style="display:flex;gap:0.5rem;">
                <select name="bulan" style="border-radius:0.5rem;padding:0.5rem 1rem;font-size:1.1rem;">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == $bulan ? 'selected' : '' }}>{{ DateTime::createFromFormat('!m', $i)->format('F') }}</option>
                    @endfor
                </select>
                <select name="tahun" style="border-radius:0.5rem;padding:0.5rem 1rem;font-size:1.1rem;">
                    @for ($y = Carbon::now()->year - 2; $y <= Carbon::now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" style="background:#007bff;color:#fff;padding:0.5rem 1.2rem;border-radius:0.5rem;font-size:1.1rem;font-weight:bold;">Tampilkan</button>
            </form>
        </div>
        <div style="overflow-x:auto;margin-top:1rem;">
            <table class=" table-bordered" style="min-width:100%;border-radius:0.5rem;box-shadow:0 2px 8px #0001;background:#fff;">
                <thead>
                    <tr bgcolor="#FFA800" align="center">
                      <td style="font-weight:bold;font-size:1.1rem;">Tipe Kamar</td>
                      @foreach ($jenisKamar as $jenis)
                        @php
                          $jumlahJenis = collect($kamarList)->where('jenis', $jenis)->count();
                        @endphp
                        <td colspan="{{ $jumlahJenis }}" style="font-weight:bold;font-size:1.1rem;background:#FFD700;color:#333;">{{ $jenis }}</td>
                      @endforeach
                      <td rowspan="2" style="font-weight:bold;font-size:1.1rem;background:#007bff;color:#fff;vertical-align:middle;">Total Terisi</td>
                    </tr>
                    <tr bgcolor="#FFA800" align="center">
                      <td style="font-weight:bold;font-size:1.1rem;">Tanggal</td>
                      @foreach ($kamarList as $kamar)
                        <td style="font-weight:bold;font-size:1.1rem;">{{ $kamar->nomor_kamar }}</td>
                      @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tanggalList as $tanggal)
                        @php 
                            $totalTerisi = 0; 
                            $carbonDate = \Carbon\Carbon::parse($tanggal);
                            $isWeekend = $carbonDate->isWeekend();
                            $dayName = $carbonDate->translatedFormat('l');
                            $rowBg = $isWeekend ? 'skyblue' : '#fff';
                        @endphp
                        <tr bgcolor="{{ $rowBg }}" style="text-align:center;">
                            <td style="width:80px;font-weight:bold;font-size:1rem;">
                                <span style="font-weight:bold;font-size:1rem;">{{ $carbonDate->format('d M') }}</span><br>
                                <span style="font-size:0.9rem;color:#888;">{{ $dayName }}</span>
                            </td>
                            @foreach ($kamarList as $kamar)
                                @php
                                    $status = $statusBooking[$tanggal][$kamar->id] ?? 'kosong';
                                    $warna = [
                                        'kosong' => '#fff',
                                        'dipesan_langsung' => '#ff5757ff',
                                        'dipesan_eticket' => '#59b4ffff',
                                        'ditempati_langsung' => '#ff5757ff',
                                        'ditempati_eticket' => '#59b4ffff',
                                    ][$status];
                                    $icon = [
                                        'kosong' => '',
                                        'dipesan_langsung' => '&#128197;',
                                        'dipesan_eticket' => '&#128197;',
                                        'ditempati_langsung' => '&#128273;',
                                        'ditempati_eticket' => '&#128273;',
                                    ][$status];
                                    if (str_contains($status, 'ditempati')) $totalTerisi++;
                                @endphp
                                <td style="background:{{ $warna }};font-size:1.3rem;font-weight:bold;">{!! $icon !!}</td>
                            @endforeach
                            <td style="background:#007bff;color:#fff;font-weight:bold;">{{ $totalTerisi }}</td>
                        </tr>
                    @endforeach
                    @php
                        $totalKamarTerisiBulan = 0;
                        foreach ($tanggalList as $tanggal) {
                            foreach ($kamarList as $kamar) {
                                $status = $statusBooking[$tanggal][$kamar->id] ?? 'kosong';
                                if (str_contains($status, 'ditempati')) $totalKamarTerisiBulan++;
                            }
                        }
                    @endphp
                    <tr bgcolor="#FFD700" style="text-align:center;font-weight:bold;">
                        <td colspan= 18>Total Kamar Terisi Bulan Ini</td>
                        <td colspan= 1 >{{ $totalKamarTerisiBulan }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
    </div>
@endsection