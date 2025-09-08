@extends('layouts.templateowner')

@section('penginap')
        <div class="container">
          <div class="page-inner">
            <div class="page-header">
              <h4 class="page-title">Daftar Penginap</h4>
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
                  <a href="#">Penginap</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
              </ul>
            </div>

                <div class="flex justify-between items-center mb-4">
                    <a href="{{ route('penginap.create') }}" class="btn btn-primary text-white px-4 py-2 rounded shadow hover:bg-blue-700">+ Tambah Pelanggan</a>
                </div>
                <table id="tabel-pelanggan" class="min-w-full border rounded-lg shadow-lg bg-white">
                    <thead class="bg-blue-200">
                        <tr>
                            <th class="border px-4 py-2">Nama</th>
                            <th class="border px-4 py-2">Email</th>
                            <th class="border px-4 py-2">Telepon</th>
                            <th class="border px-4 py-2">Alamat</th>
                            <th class="border px-4 py-2">Jenis Identitas</th>
                            <th class="border px-4 py-2">Nomor Identitas</th>
                            <th class="border px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penginap as $p)
                        <tr>
                            <td class="border px-4 py-2">{{ $p->nama }}</td>
                            <td class="border px-4 py-2">{{ $p->email }}</td>
                            <td class="border px-4 py-2">{{ $p->telepon }}</td>
                            <td class="border px-4 py-2">{{ $p->alamat }}</td>
                            <td class="border px-4 py-2">{{ $p->jenis_identitas }}</td>
                            <td class="border px-4 py-2">{{ $p->nomor_identitas }}</td>
                            <td class="border px-4 py-2">
                                <a href="{{ route('penginap.edit', $p->id) }}" class="btn btn-warning text-white px-2 py-1 rounded hover:bg-yellow-500">Edit</a>
                                <form action="{{ route('penginap.destroy', $p->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger text-white px-2 py-1 rounded hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
        <div class="mt-4 p-3 rounded bg-gray-50">
          {{ $penginap->links() }}
        </div>
            </div>
            <!-- DataTables CDN -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
            <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script>
            $(document).ready(function() {
                $('#tabel-pelanggan').DataTable({
                    paging: true,
                    searching: true,
                    info: false,
                    lengthChange: false,
                    pageLength: 10,
                    language: {
                        paginate: {
                            previous: 'Sebelumnya',
                            next: 'Berikutnya'
                        },
                        search: 'Cari:',
                        zeroRecords: 'Data tidak ditemukan',
                        infoEmpty: 'Tidak ada data',
                    }
                });
            });
            </script>
          </div>
        </div>
@endsection