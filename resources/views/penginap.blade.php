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
        <!-- isi -->
    <!-- Header + Button -->
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;">
      <button type="button" id="btnTambahPelanggan" class="btn btn-primary">
        + Tambah Pelanggan
      </button>
    </div>

    <!-- Modal Overlay -->
    <div id="modalPelanggan" class="modal-overlay" style="display:none;">
      <div class="modal-card">
        <button type="button" class="modal-close" id="closeModalPelanggan" aria-label="Tutup">&times;</button>
        <h3 style="margin-top:0;">Tambah Penginap Baru</h3>

        <form action="{{ route('penginap.create') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="block mb-1 font-medium">Nama</label>
            <input type="text" name="nama" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Telepon</label>
            <input type="text" name="telepon" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Alamat</label>
            <input type="text" name="alamat" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Jenis Identitas</label>
            <input type="text" name="jenis_identitas" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Nomor Identitas</label>
            <input type="text" name="nomor_identitas" class="form-control" required>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" class="btn btn-light" id="batalModalPelanggan">Batal</button>
            <button type="submit" class="btn btn-success">Simpan</button>
          </div>
        </form>
      </div>
    </div>
    <!-- End Modal Overlay -->
    <!-- Table -->
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
      {{ $penginap->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>
  

  <!-- Minimal styles for modal -->
  <style>
    .modal-overlay {
      position: fixed; inset: 0; z-index: 1050;
      display: none; align-items: center; justify-content: center;
      background: rgba(0,0,0,.45);
      padding: 16px;
    }
    .modal-card {
      width: 100%; max-width: 520px;
      background: #fff; border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0,0,0,.2);
      padding: 20px; position: relative;
    }
    .modal-close {
      position: absolute; top: 8px; right: 12px;
      border: 0; background: transparent;
      font-size: 28px; line-height: 1; cursor: pointer; color: #999;
    }
    .modal-close:hover { color: #e74c3c; }
    .form-control {
      width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px;
    }
    .mb-3 { margin-bottom: 12px; }
    .btn { cursor: pointer; }
  </style>

  <!-- DataTables CDN (kept as in your file) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Initialize DataTables with server-side pagination (disable client paging)
      if (window.jQuery && $('#tabel-pelanggan').length) {
        $('#tabel-pelanggan').DataTable({
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
      }
      const openBtn  = document.getElementById('btnTambahPelanggan');
      const modal    = document.getElementById('modalPelanggan');
      const closeBtn = document.getElementById('closeModalPelanggan');
      const cancelBtn= document.getElementById('batalModalPelanggan');

      function openModal()  { modal.style.display = 'flex'; }
      function closeModal() { modal.style.display = 'none'; }

      openBtn && openBtn.addEventListener('click', openModal);
      closeBtn && closeBtn.addEventListener('click', closeModal);
      cancelBtn && cancelBtn.addEventListener('click', closeModal);

      // Click backdrop to close
      modal && modal.addEventListener('click', function(e){
        if (e.target === modal) closeModal();
      });
    });
  </script>
</div>
@endsection