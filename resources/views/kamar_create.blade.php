@extends('layouts.templateowner')

@section('content')
<div class="container mx-auto py-8">
    <h2 class="text-2xl font-bold mb-4">Tambah Kamar</h2>
    <form action="{{ route('kamar.store') }}" method="POST" class="bg-white p-6 rounded shadow-md">
        @csrf
        <div class="mb-4">
            <label class="block font-semibold mb-1">Nomor Kamar</label>
            <input type="text" name="nomor_kamar" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Tipe</label>
            <input type="text" name="tipe" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Kapasitas</label>
            <input type="number" name="kapasitas" class="border rounded w-full px-3 py-2" min="1" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Harga</label>
            <input type="number" name="harga" class="border rounded w-full px-3 py-2" min="0" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Status</label>
            <select name="status" class="border rounded w-full px-3 py-2" required>
                <option value="tersedia">Tersedia</option>
                <option value="terisi">Terisi</option>
                <option value="perawatan">Perawatan</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Deskripsi</label>
            <textarea name="deskripsi" class="border rounded w-full px-3 py-2"></textarea>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Simpan</button>
        <a href="{{ route('kamar.index') }}" class="ml-2 text-gray-600">Batal</a>
    </form>
</div>
@endsection
// ...existing code...
