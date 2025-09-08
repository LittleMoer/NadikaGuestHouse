@extends('layouts.templateowner')

@section('booking')
        <div class="container">
          <div class="page-inner">
            <div class="page-header">
              <h4 class="page-title">Dashboard</h4>
              <ul class="breadcrumbs">
                <li class="nav-home">
                  <a href="/dashboard">
                    <i class="icon-home"></i>
                  </a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">Booking</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
              </ul>
            </div>
            @php
                $jenisKamar = ['Standard', 'Deluxe', 'Suite', 'VIP'];
                $kamarList = [];
                for ($i = 1; $i <= 20; $i++) {
                    $kamarList[] = (object)[
                        'id' => $i,
                        'nomor_kamar' => str_pad($i, 3, '0', STR_PAD_LEFT),
                        'jenis' => $jenisKamar[($i-1)%4],
                        'kapasitas' => rand(1,4),
                        'status' => ['tersedia','dipesan','ditempati'][rand(0,2)]
                    ];
                }
                $tanggalBooking = request()->get('tanggal', date('Y-m-d'));
            @endphp
            <div class="container py-4">
                <form method="GET" class="mb-4 flex gap-2 items-center">
                    <label for="tanggal" class="font-semibold">Tanggal Booking:</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggalBooking }}" class="border rounded px-2 py-1">
                    <button type="submit" class="btn btn-primary text-white px-4 py-2 rounded shadow hover:bg-blue-700">Tampilkan</button>
                </form>
                <div class="row">
                    @foreach ($jenisKamar as $jenis)
                        <div class="col-12 mb-3">
                            <h3 class="font-bold text-lg mb-2">{{ $jenis }}</h3>
                            <div class="row">
                                @foreach (collect($kamarList)->where('jenis', $jenis) as $kamar)
                                    <div class="col-sm-6 col-md-3 mb-3">
                                        <div class="card card-stats card-primary card-round {{ $kamar->status == 'tersedia' ? 'border-success' : ($kamar->status == 'dipesan' ? 'border-warning' : 'border-danger') }}" style="border-width:2px;">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-5">
                                                        <div class="icon-big text-center">
                                                            <i class="fas fa-bed"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col-7 col-stats">
                                                        <div class="numbers">
                                                            <p class="card-category">Kamar {{ $kamar->nomor_kamar }}</p>
                                                            <h4 class="card-title">{{ ucfirst($kamar->status) }}</h4>
                                                            <span class="badge {{ $kamar->status == 'tersedia' ? 'bg-success' : ($kamar->status == 'dipesan' ? 'bg-warning' : 'bg-danger') }} text-white">{{ $tanggalBooking }}</span>
                                                            <div class="mt-2">
                                                                @if($kamar->status == 'tersedia')
                                                                    <button class="btn btn-primary w-100">Pilih Kamar</button>
                                                                @else
                                                                    <button class="btn btn-warning w-100" disabled>Tidak Tersedia</button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
          </div>
        </div>
@endsection