@extends('layouts.templateowner')

@section('kamar')
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
        <!-- isi -->
        <div class="flex justify-between items-center mb-4">
            <a href="{{ route('kamar.create') }}"
                class="btn btn-primary text-white px-4 py-2 rounded shadow hover:bg-blue-700">+ Tambah Kamar</a>
        </div>
        <table id="tabel-kamar" class="min-w-full border rounded-lg shadow-lg bg-white">
            <thead class="bg-blue-200">
                <tr>
                    <th class="border px-4 py-2">Nomor Kamar</th>
                    <th class="border px-4 py-2">Tipe</th>
                    <th class="border px-4 py-2">Kapasitas</th>
                    <th class="border px-4 py-2">Harga</th>
                    <th class="border px-4 py-2">Status</th>
                    <th class="border px-4 py-2">Deskripsi</th>
                    <th class="border px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kamar as $k)
                <tr>
                    <td class="border px-4 py-2">{{ $k->nomor_kamar }}</td>
                    <td class="border px-4 py-2">{{ $k->tipe }}</td>
                    <td class="border px-4 py-2">{{ $k->kapasitas }}</td>
                    <td class="border px-4 py-2">Rp {{ number_format($k->harga, 0, ',', '.') }}</td>
                    <td class="border px-4 py-2">{{ ucfirst($k->status) }}</td>
                    <td class="border px-4 py-2">{{ $k->deskripsi }}</td>
                    <td class="border px-4 py-2">
                        <a href="{{ route('kamar.edit', $k->id) }}"
                            class="btn btn-warning text-white px-2 py-1 rounded hover:bg-yellow-500">Edit</a>
                        <form action="{{ route('kamar.destroy', $k->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger text-white px-2 py-1 rounded hover:bg-red-600"
                                onclick="return confirm('Yakin ingin menghapus kamar?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4 p-3 rounded bg-gray-50">
            {{ $kamar->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
        <!-- DataTables CDN -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script>
        $(document).ready(function() {
            // Disable client-side paging; use Laravel pagination instead
            $('#tabel-kamar').DataTable({
                paging: false,
                searching: true,
                info: false,
                lengthChange: false,
                language: {
                    search: 'Cari:',
                    zeroRecords: 'Data tidak ditemukan',
                    infoEmpty: 'Tidak ada data',
                }
            });
        });
        </script>
        <!-- end isi -->
    </div>
</div>
</div>
@endsection