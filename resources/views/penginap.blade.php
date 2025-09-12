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
      <button type="button" class="btn btn-primary text-white px-4 py-2 rounded shadow hover:bg-blue-700" id="btnTambahPelanggan">
        + Tambah Pelanggan
      </button>
    </div>

    <!-- Modal Card -->
    <div id="modalPelanggan" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-2xl font-bold" id="closeModalPelanggan">&times;</button>
        <h3 class="text-xl font-semibold mb-4">Tambah Penginap Baru</h3>
        <form action="{{ route('penginap.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="block mb-1 font-medium">Nama</label>
            <input type="text" name="nama" class="form-control w-full border px-3 py-2 rounded" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Email</label>
            <input type="email" name="email" class="form-control w-full border px-3 py-2 rounded" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Telepon</label>
            <input type="text" name="telepon" class="form-control w-full border px-3 py-2 rounded" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Alamat</label>
            <input type="text" name="alamat" class="form-control w-full border px-3 py-2 rounded" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Jenis Identitas</label>
            <input type="text" name="jenis_identitas" class="form-control w-full border px-3 py-2 rounded" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Nomor Identitas</label>
            <input type="text" name="nomor_identitas" class="form-control w-full border px-3 py-2 rounded" required>
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-success px-4 py-2 rounded text-white">Simpan</button>
          </div>
        </form>
      </div>
    </div>
    <!-- End Modal Card -->

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

      // Modal logic
      $('#btnTambahPelanggan').on('click', function() {
        $('#modalPelanggan').removeClass('hidden');
      });
      $('#closeModalPelanggan').on('click', function() {
        $('#modalPelanggan').addClass('hidden');
      });
      $('#modalPelanggan').on('click', function(e) {
        if (e.target === this) {
          $(this).addClass('hidden');
        }
      });
    });
  </script>
</div>
</div>
@endsection