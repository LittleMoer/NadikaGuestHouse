@extends('layouts.app_layout')

@section('kamar')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Daftar Kamar</h4>
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
                    <a href="#">Kamar</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
            </ul>
        </div>
        <!-- isi -->
        <div class="flex justify-between items-center mb-4">
            <button type="button" id="btnTambahKamar" class="btn btn-primary text-white px-4 py-2 rounded shadow hover:bg-blue-700">+ Tambah Kamar</button>
        </div>
        <table id="tabel-kamar" class="min-w-full border rounded-lg shadow-lg bg-white">
            <thead class="bg-blue-200">
                <tr>
                    <th class="border px-4 py-2">Nomor Kamar</th>
                    <th class="border px-4 py-2">Tipe</th>
                    <th class="border px-4 py-2">Kapasitas</th>
                    <th class="border px-4 py-2">Harga</th>
                    <th class="border px-4 py-2">Status</th>
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
                    <td class="border px-4 py-2">
                        @php $statusLabelMap = [1=>'Tersedia',2=>'Terisi',3=>'Perawatan']; @endphp
                        {{ $statusLabelMap[$k->status] ?? $k->status }}
                    </td>
                    <td class="border px-4 py-2">
                        <button type="button"
                            class="btn btn-warning text-white px-2 py-1 rounded hover:bg-yellow-500 btn-edit-kamar"
                            data-id="{{ $k->id }}"
                            data-nomor_kamar="{{ $k->nomor_kamar }}"
                            data-tipe="{{ $k->tipe }}"
                            data-kapasitas="{{ $k->kapasitas }}"
                            data-harga="{{ $k->harga }}"
                            data-status="{{ $k->status }}"
                            data-deskripsi="{{ $k->deskripsi }}"
                        >Edit</button>
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
        <!-- Create Modal for Kamar -->
        <div id="modalCreateKamar" class="modal-overlay" aria-hidden="true">
            <div class="modal-card">
                <button type="button" class="modal-close" id="closeModalCreateKamar" aria-label="Tutup">&times;</button>
                <h3 style="margin-top:0;">Tambah Kamar</h3>

                @if ($errors->any())
                    <div class="alert alert-danger" style="margin-bottom:12px;">
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('kamar.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Nomor Kamar</label>
                        <input type="text" name="nomor_kamar" class="form-control" value="{{ old('nomor_kamar') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Tipe</label>
                        <input type="text" name="tipe" class="form-control" value="{{ old('tipe') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Kapasitas</label>
                        <input type="number" min="1" name="kapasitas" class="form-control" value="{{ old('kapasitas') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Harga (Rp)</label>
                        <input type="number" min="0" step="1" name="harga" class="form-control" value="{{ old('harga') }}" required>
                    </div>
                    @php $oldCreateStatus = (string)old('status'); @endphp
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="">Pilih status</option>
                            <option value="1" {{ $oldCreateStatus==='1' ? 'selected' : '' }}>Tersedia</option>
                            <option value="2" {{ $oldCreateStatus==='2' ? 'selected' : '' }}>Terisi</option>
                            <option value="3" {{ $oldCreateStatus==='3' ? 'selected' : '' }}>Perawatan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                        <button type="button" class="btn btn-light" id="batalModalCreateKamar">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Modal for Kamar -->
        <div id="modalEditKamar" class="modal-overlay" aria-hidden="true">
            <div class="modal-card">
                <button type="button" class="modal-close" id="closeModalEditKamar" aria-label="Tutup">&times;</button>
                <h3 style="margin-top:0;">Edit Kamar</h3>

                @if ($errors->hasBag('kamar_edit') && $errors->kamar_edit->any())
                    <div class="alert alert-danger" style="margin-bottom:12px;">
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($errors->kamar_edit->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="formEditKamar" action="{{ url('/kamar') }}/__ID__" method="POST" data-action-base="{{ url('/kamar') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="kamar_edit_id" value="{{ old('id') }}">
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Nomor Kamar</label>
                        <input type="text" name="nomor_kamar" id="edit_nomor_kamar" class="form-control" value="{{ old('nomor_kamar') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Tipe</label>
                        <input type="text" name="tipe" id="edit_tipe" class="form-control" value="{{ old('tipe') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Kapasitas</label>
                        <input type="number" min="1" name="kapasitas" id="edit_kapasitas" class="form-control" value="{{ old('kapasitas') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Harga (Rp)</label>
                        <input type="number" min="0" step="1" name="harga" id="edit_harga" class="form-control" value="{{ old('harga') }}" required>
                    </div>
                    @php
                        $oldStatus = old('status');
                        if ($oldStatus === 'tersedia') $oldStatus = '1';
                        elseif ($oldStatus === 'terisi') $oldStatus = '2';
                        elseif ($oldStatus === 'perawatan') $oldStatus = '3';
                    @endphp
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="">Pilih status</option>
                            <option value="1" {{ (string)$oldStatus==='1' ? 'selected' : '' }}>Tersedia</option>
                            <option value="2" {{ (string)$oldStatus==='2' ? 'selected' : '' }}>Terisi</option>
                            <option value="3" {{ (string)$oldStatus==='3' ? 'selected' : '' }}>Perawatan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                        <button type="button" class="btn btn-light" id="batalModalEditKamar">Batal</button>
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- DataTables CDN -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <style>
            .modal-overlay { position: fixed; inset: 0; z-index: 1050; display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,.45); padding:16px; opacity:0; visibility:hidden; transition: opacity 200ms ease, visibility 0s linear 200ms; }
            .modal-overlay.show { opacity:1; visibility:visible; transition: opacity 200ms ease; }
            .modal-card { width:100%; max-width:520px; background:#fff; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.2); padding:20px; position:relative; transform: translateY(12px) scale(0.98); opacity:.95; transition: transform 250ms ease, opacity 200ms ease; }
            .modal-overlay.show .modal-card { transform: translateY(0) scale(1); opacity:1; }
            .modal-close { position:absolute; top:8px; right:12px; border:0; background:transparent; font-size:28px; line-height:1; cursor:pointer; color:#999; }
            .modal-close:hover { color:#e74c3c; }
        </style>
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

            // Edit modal handlers
            const editModal = document.getElementById('modalEditKamar');
            const closeEditBtn = document.getElementById('closeModalEditKamar');
            const cancelEditBtn = document.getElementById('batalModalEditKamar');
            const form = document.getElementById('formEditKamar');
            const actionBase = form ? form.getAttribute('data-action-base') : '';
            const idInput = document.getElementById('kamar_edit_id');
            const nomorKamar = document.getElementById('edit_nomor_kamar');
            const tipe = document.getElementById('edit_tipe');
            const kapasitas = document.getElementById('edit_kapasitas');
            const harga = document.getElementById('edit_harga');
            const status = document.getElementById('edit_status');
            const deskripsi = document.getElementById('edit_deskripsi');

            function openEdit(){ if (editModal) { editModal.classList.add('show'); editModal.setAttribute('aria-hidden','false'); } }
            function closeEdit(){ if (editModal) { editModal.classList.remove('show'); editModal.setAttribute('aria-hidden','true'); } }

            document.querySelectorAll('.btn-edit-kamar').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const id = this.getAttribute('data-id');
                    if (idInput) idInput.value = id || '';
                    if (nomorKamar) nomorKamar.value = this.getAttribute('data-nomor_kamar') || '';
                    if (tipe) tipe.value = this.getAttribute('data-tipe') || '';
                    if (kapasitas) kapasitas.value = this.getAttribute('data-kapasitas') || '';
                    if (harga) harga.value = this.getAttribute('data-harga') || '';
                    if (status) status.value = (this.getAttribute('data-status') || '').toString();
                    if (deskripsi) deskripsi.value = this.getAttribute('data-deskripsi') || '';

                    if (actionBase && form) {
                        form.setAttribute('action', actionBase + '/' + id);
                    }
                    openEdit();
                });
            });

            closeEditBtn && closeEditBtn.addEventListener('click', closeEdit);
            cancelEditBtn && cancelEditBtn.addEventListener('click', closeEdit);
            editModal && editModal.addEventListener('click', function(e){ if (e.target === editModal) closeEdit(); });
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeEdit(); });

            // Auto-open on validation errors for 'kamar_edit' bag
            @if ($errors->hasBag('kamar_edit') && $errors->kamar_edit->any())
                if (form) {
                    const oldId = '{{ old('id') }}';
                    if (oldId) {
                        form.setAttribute('action', (actionBase ? actionBase : '{{ url('/kamar') }}') + '/' + oldId);
                    }
                }
                openEdit();
            @endif

            // Create modal handlers
            const createModal = document.getElementById('modalCreateKamar');
            const openCreateBtn = document.getElementById('btnTambahKamar');
            const closeCreateBtn = document.getElementById('closeModalCreateKamar');
            const cancelCreateBtn = document.getElementById('batalModalCreateKamar');
            function openCreate(){ if (createModal) { createModal.classList.add('show'); createModal.setAttribute('aria-hidden','false'); } }
            function closeCreate(){ if (createModal) { createModal.classList.remove('show'); createModal.setAttribute('aria-hidden','true'); } }
            openCreateBtn && openCreateBtn.addEventListener('click', openCreate);
            closeCreateBtn && closeCreateBtn.addEventListener('click', closeCreate);
            cancelCreateBtn && cancelCreateBtn.addEventListener('click', closeCreate);
            createModal && createModal.addEventListener('click', function(e){ if (e.target === createModal) closeCreate(); });
            // Auto-open create modal if validation errors exist in default bag
            @if ($errors->any())
                openCreate();
            @endif
        });
        </script>
        <!-- end isi -->
    </div>
</div>
</div>
@endsection