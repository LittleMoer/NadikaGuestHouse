@extends('layouts.app_layout')

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
    <div class="flex justify-between items-center mb-4">
      <button type="button" id="btnTambahPelanggan" class="btn btn-primary">
        + Tambah Pelanggan
      </button>
    </div>

  <!-- Modal Overlay -->
  <div id="modalPelanggan" class="modal-overlay" aria-hidden="true">
      <div class="modal-card">
        <button type="button" class="modal-close" id="closeModalPelanggan" aria-label="Tutup">&times;</button>
        <h3 style="margin-top:0;">Tambah Penginap Baru</h3>

        @if ($errors->any())
          <div class="alert alert-danger" style="margin-bottom:12px;">
            <ul style="margin:0;padding-left:18px;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <form action="{{ route('penginap.create') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="block mb-1 font-medium">Nama</label>
            <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Telepon</label>
            <input type="text" name="telepon" class="form-control" value="{{ old('telepon') }}" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Alamat</label>
            <input type="text" name="alamat" class="form-control" value="{{ old('alamat') }}" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Jenis Identitas</label>
            <select name="jenis_identitas" id="jenis_identitas" class="form-control">
              <option value="" {{ old('jenis_identitas')==='' ? 'selected' : '' }}>Pilih jenis</option>
              <option value="KTP" {{ old('jenis_identitas')==='KTP' ? 'selected' : '' }}>KTP</option>
              <option value="SIM" {{ old('jenis_identitas')==='SIM' ? 'selected' : '' }}>SIM</option>
              <option value="Kartu Pelajar" {{ old('jenis_identitas')==='Kartu Pelajar' ? 'selected' : '' }}>Kartu Pelajar</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Nomor Identitas</label>
            <input type="text" name="nomor_identitas" class="form-control" value="{{ old('nomor_identitas') }}">
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Tempat Lahir</label>
            <input type="text" name="tempat_lahir" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="block mb-1 font-medium">Kewarganegaraan</label>
            <input type="text" name="kewarganegaraan" class="form-control" required>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" class="btn btn-light" id="batalModalPelanggan">Batal</button>
            <button type="submit" class="btn btn-success">Simpan</button>
          </div>
        </form>
      </div>
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
            <button
              type="button"
              class="btn btn-warning text-white px-2 py-1 rounded hover:bg-yellow-500 btn-edit-pelanggan"
              data-id="{{ $p->id }}"
              data-nama="{{ $p->nama }}"
              data-email="{{ $p->email }}"
              data-telepon="{{ $p->telepon }}"
              data-alamat="{{ $p->alamat }}"
              data-jenis_identitas="{{ $p->jenis_identitas }}"
              data-nomor_identitas="{{ $p->nomor_identitas }}"
              data-tempat_lahir="{{ $p->tempat_lahir }}"
              data-tanggal_lahir="{{ $p->tanggal_lahir }}"
              data-kewarganegaraan="{{ $p->kewarganegaraan }}"
            >Edit</button>
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
      display: flex; align-items: center; justify-content: center;
      background: rgba(0,0,0,.45);
      padding: 16px;
      opacity: 0; visibility: hidden;
      transition: opacity 200ms ease, visibility 0s linear 200ms;
    }
    .modal-overlay.show { opacity: 1; visibility: visible; transition: opacity 200ms ease; }
    .modal-card {
      width: 100%; max-width: 520px;
      background: #fff; border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0,0,0,.2);
      padding: 20px; position: relative;
      transform: translateY(12px) scale(0.98);
      opacity: 0.95;
      transition: transform 250ms ease, opacity 200ms ease;
    }
    .modal-overlay.show .modal-card { transform: translateY(0) scale(1); opacity: 1; }
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

  <!-- Edit Modal Overlay -->
  <div id="modalEditPelanggan" class="modal-overlay" aria-hidden="true">
    <div class="modal-card">
      <button type="button" class="modal-close" id="closeModalEditPelanggan" aria-label="Tutup">&times;</button>
      <h3 style="margin-top:0;">Edit Data Penginap</h3>

      @if ($errors->hasBag('edit') && $errors->edit->any())
        <div class="alert alert-danger" style="margin-bottom:12px;">
          <ul style="margin:0;padding-left:18px;">
            @foreach ($errors->edit->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="formEditPelanggan" action="{{ url('/penginap') }}/__ID__" method="POST" data-action-base="{{ url('/penginap') }}">
        @csrf
        <input type="hidden" name="id" id="edit_id" value="{{ old('id') }}">
        <div class="mb-3">
          <label class="block mb-1 font-medium">Nama</label>
          <input type="text" name="nama" id="edit_nama" class="form-control" value="{{ old('nama') }}" required>
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Email</label>
          <input type="email" name="email" id="edit_email" class="form-control" value="{{ old('email') }}">
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Telepon</label>
          <input type="text" name="telepon" id="edit_telepon" class="form-control" value="{{ old('telepon') }}" required>
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Alamat</label>
          <input type="text" name="alamat" id="edit_alamat" class="form-control" value="{{ old('alamat') }}" required>
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Jenis Identitas</label>
          <select name="jenis_identitas" id="edit_jenis_identitas" class="form-control">
            <option value="" {{ old('jenis_identitas')==='' ? 'selected' : '' }}>Pilih jenis</option>
            <option value="KTP" {{ old('jenis_identitas')==='KTP' ? 'selected' : '' }}>KTP</option>
            <option value="SIM" {{ old('jenis_identitas')==='SIM' ? 'selected' : '' }}>SIM</option>
            <option value="Kartu Pelajar" {{ old('jenis_identitas')==='Kartu Pelajar' ? 'selected' : '' }}>Kartu Pelajar</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Nomor Identitas</label>
          <input type="text" name="nomor_identitas" id="edit_nomor_identitas" class="form-control" value="{{ old('nomor_identitas') }}">
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Tempat Lahir</label>
          <input type="text" name="tempat_lahir" id="edit_tempat_lahir" class="form-control" value="{{ old('tempat_lahir') }}">
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}">
        </div>
        <div class="mb-3">
          <label class="block mb-1 font-medium">Kewarganegaraan</label>
          <input type="text" name="kewarganegaraan" id="edit_kewarganegaraan" class="form-control" value="{{ old('kewarganegaraan') }}">
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" class="btn btn-light" id="batalModalEditPelanggan">Batal</button>
          <button type="submit" class="btn btn-success">Update</button>
        </div>
      </form>
    </div>
  </div>

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

  // Edit modal elements
  const editModal = document.getElementById('modalEditPelanggan');
  const closeEditBtn = document.getElementById('closeModalEditPelanggan');
  const cancelEditBtn = document.getElementById('batalModalEditPelanggan');
  const editForm = document.getElementById('formEditPelanggan');
  const actionBase = editForm ? editForm.getAttribute('data-action-base') : '';
  const editId = document.getElementById('edit_id');
  const editNama = document.getElementById('edit_nama');
  const editEmail = document.getElementById('edit_email');
  const editTelepon = document.getElementById('edit_telepon');
  const editAlamat = document.getElementById('edit_alamat');
  const editJenis = document.getElementById('edit_jenis_identitas');
  const editNomorId = document.getElementById('edit_nomor_identitas');
  const editTempat = document.getElementById('edit_tempat_lahir');
  const editTanggal = document.getElementById('edit_tanggal_lahir');
  const editWarga = document.getElementById('edit_kewarganegaraan');

  function openModal()  { modal.classList.add('show'); modal.setAttribute('aria-hidden', 'false'); }
  function closeModal() { modal.classList.remove('show'); modal.setAttribute('aria-hidden', 'true'); }
  function openEditModal() { editModal && (editModal.classList.add('show'), editModal.setAttribute('aria-hidden','false')); }
  function closeEditModal(){ editModal && (editModal.classList.remove('show'), editModal.setAttribute('aria-hidden','true')); }

      openBtn && openBtn.addEventListener('click', openModal);
      closeBtn && closeBtn.addEventListener('click', closeModal);
      cancelBtn && cancelBtn.addEventListener('click', closeModal);

      // Click backdrop to close
      modal && modal.addEventListener('click', function(e){
        if (e.target === modal) closeModal();
      });
      editModal && editModal.addEventListener('click', function(e){
        if (e.target === editModal) closeEditModal();
      });

      // Press Escape to close
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeModal(); closeEditModal(); }
      });

      // Auto open modal if there are validation errors
      @if ($errors->any())
        openModal();
      @endif

      // Wire Edit buttons to open and populate the edit modal
      const editButtons = document.querySelectorAll('.btn-edit-pelanggan');
      editButtons.forEach(function(btn){
        btn.addEventListener('click', function(){
          if (!editForm) return;
          const id = this.getAttribute('data-id');
          editId && (editId.value = id || '');
          editNama && (editNama.value = this.getAttribute('data-nama') || '');
          editEmail && (editEmail.value = this.getAttribute('data-email') || '');
          editTelepon && (editTelepon.value = this.getAttribute('data-telepon') || '');
          editAlamat && (editAlamat.value = this.getAttribute('data-alamat') || '');
          const jenisVal = this.getAttribute('data-jenis_identitas') || '';
          if (editJenis) {
            editJenis.value = jenisVal;
          }
          editNomorId && (editNomorId.value = this.getAttribute('data-nomor_identitas') || '');
          editTempat && (editTempat.value = this.getAttribute('data-tempat_lahir') || '');
          editTanggal && (editTanggal.value = this.getAttribute('data-tanggal_lahir') || '');
          editWarga && (editWarga.value = this.getAttribute('data-kewarganegaraan') || '');

          // Set form action to /penginap/{id}
          if (actionBase) {
            editForm.setAttribute('action', actionBase + '/' + id);
          }

          openEditModal();
        });
      });

      // Close buttons for edit modal
      closeEditBtn && closeEditBtn.addEventListener('click', closeEditModal);
      cancelEditBtn && cancelEditBtn.addEventListener('click', closeEditModal);

      // Auto open edit modal if there are validation errors in 'edit' bag (server-side)
      @if ($errors->hasBag('edit') && $errors->edit->any())
        if (editForm) {
          const oldId = '{{ old('id') }}';
          if (oldId) {
            editForm.setAttribute('action', (actionBase ? actionBase : '{{ url('/penginap') }}') + '/' + oldId);
          }
        }
        openEditModal();
      @endif
    });
  </script>
</div>
@endsection