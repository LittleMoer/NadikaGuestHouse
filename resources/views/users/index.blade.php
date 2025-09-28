@extends('layouts.app_layout')

@section('dashboard')
<div class="container">
  <div class="page-inner">
    <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h3 class="fw-bold mb-0">Manajemen Akun</h3>
      <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">Tambah Akun</a>
    </div>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width:72px">#</th>
                <th>Nama</th>
                <th>Email</th>
                <th style="width:140px">Role</th>
                <th style="width:200px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($users as $index => $u)
                <tr>
                  <td>{{ ($users->currentPage()-1) * $users->perPage() + $index + 1 }}</td>
                  <td>{{ $u->name }}</td>
                  <td>{{ $u->email }}</td>
                  <td>
                    <span class="badge {{ $u->role==='owner' ? 'badge-success' : 'badge-info' }}">{{ strtoupper($u->role) }}</span>
                  </td>
                  <td>
                    <a href="{{ route('users.edit', $u) }}" class="btn btn-warning btn-sm me-1">Edit</a>
                    <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus akun ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center">Belum ada data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="small text-muted">
            Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} data
          </div>
          <div>
            {{ $users->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
