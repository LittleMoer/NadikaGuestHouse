@extends('layouts.app_layout')

@section('dashboard')
<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <h3 class="fw-bold mb-3">Tambah Akun</h3>
      <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
    <div class="card">
      <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama</label>
              <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
              @error('name')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
              @error('email')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
              @error('password')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Konfirmasi Password</label>
              <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select name="role" class="form-select" required>
                <option value="owner" {{ old('role')==='owner' ? 'selected' : '' }}>Owner</option>
                <option value="admin" {{ old('role')==='admin' ? 'selected' : '' }}>Admin</option>
              </select>
              @error('role')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
