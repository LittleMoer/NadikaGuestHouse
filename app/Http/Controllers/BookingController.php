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
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'required|string|max:15',
            'alamat' => 'required|string|max:500',
            'jenis_identitas' => 'nullable|string|max:100',
            'nomor_identitas' => 'nullable|string|max:100',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'kewarganegaraan' => 'nullable|string|max:100',
        ]);

        // Simpan data pelanggan baru
        \App\Models\Pelanggan::create([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'email' => $request->email,
            'jenis_identitas' => $request->jenis_identitas,
            'nomor_identitas' => $request->nomor_identitas,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'kewarganegaraan' => $request->kewarganegaraan,
        ]);

        return redirect()->back()->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }
    public function penginapedit(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'required|string|max:15',
            'alamat' => 'required|string|max:500',
            'jenis_identitas' => 'nullable|string|max:100',
            'nomor_identitas' => 'nullable|string|max:100',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'kewarganegaraan' => 'nullable|string|max:100',
        ]);

        // Update data pelanggan
        $pelanggan = Pelanggan::find($request->id);
        $pelanggan->update([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'email' => $request->email,
            'jenis_identitas' => $request->jenis_identitas,
            'nomor_identitas' => $request->nomor_identitas,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'kewarganegaraan' => $request->kewarganegaraan,
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