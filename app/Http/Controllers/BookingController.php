<?php

namespace App\Http\Controllers;
use App\Models\Booking;
use App\Models\Pelanggan;
use Illuminate\Http\Request;


class BookingController extends Controller
{
    // Display a listing of bookings
    public function index()
    {
        //
    }

    // Show the form for creating a new booking
    public function create()
    {
        //
    }

    // Store a newly created booking in storage
    public function store(Request $request)
    {
        //
    }

    // Display the specified booking
    public function show($id)
    {
        //
    }

    // Show the form for editing the specified booking
    public function edit($id)
    {
        //
    }

    // Update the specified booking in storage
    public function update(Request $request, $id)
    {
        //
    }

    // Remove the specified booking from storage
    public function destroy($id)
    {
        //
    }
    public function penginap()
    {
        // Demo data: generate 5 random pelanggan jika tabel kosong
        if (Pelanggan::count() == 0) {
            for ($i = 1; $i <= 5; $i++) {
            Pelanggan::create([
                'nama' => 'Demo Penginap ' . $i,
                'alamat' => 'Alamat Demo ' . $i,
                'no_telepon' => '0812345678' . $i,
                'email' => 'demo' . $i . '@example.com',
            ]);
            }
        }
        $penginap = Pelanggan::paginate(10);
        return view('penginap', compact('penginap'));
    }
    public function penginapcreate(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'no_telepon' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
        ]);

        // Simpan data pelanggan baru
        \App\Models\Pelanggan::create([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }
    public function penginapedit(Request $request)
    {
        // Validasi input
        $request->validate([
            'id' => 'required|exists:pelanggan,id',
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'no_telepon' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
        ]);

        // Update data pelanggan
        $pelanggan = Pelanggan::find($request->id);
        $pelanggan->update([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Data pelanggan berhasil diperbarui.');
    }
    public function penginapdestroy($id)
    {
        // Hapus data pelanggan
        $pelanggan = Pelanggan::find($id);
        if ($pelanggan) {
            $pelanggan->delete();
            return redirect()->back()->with('success', 'Data pelanggan berhasil dihapus.');
        }
        return redirect()->back()->with('error', 'Pelanggan tidak ditemukan.');
    }
}