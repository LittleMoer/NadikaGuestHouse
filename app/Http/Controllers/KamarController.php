<?php
namespace App\Http\Controllers;

use App\Models\Kamar;
use Illuminate\Http\Request;

class KamarController extends Controller
{
    public function index()
    {
        $kamar = Kamar::paginate(10);
        return view('kamar', compact('kamar'));
    }

    public function create()
    {
        return view('kamar_create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_kamar' => 'required|unique:kamar',
            'tipe' => 'required',
            'kapasitas' => 'required|integer|min:1',
            'harga' => 'required|numeric|min:0',
            'status' => 'required|in:1,2,3',
            'deskripsi' => 'nullable'
        ]);
        $validated['status'] = (int)$validated['status'];
        Kamar::create($validated);
        return redirect()->route('kamar.index')->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $kamar = Kamar::findOrFail($id);
        return view('kamar_edit', compact('kamar'));
    }

    public function update(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'nomor_kamar' => 'required|unique:kamar,nomor_kamar,' . $id,
            'tipe' => 'required',
            'kapasitas' => 'required|integer|min:1',
            'harga' => 'required|numeric|min:0',
            'status' => 'required|in:1,2,3',
            'deskripsi' => 'nullable'
        ]);
        if ($validator->fails()) {
            return redirect()->route('kamar.index')->withErrors($validator, 'kamar_edit')->withInput();
        }
        $validated = $validator->validated();
        $kamar = Kamar::findOrFail($id);
    $validated['status'] = (int)$validated['status'];
    $kamar->update($validated + ['deskripsi' => $request->input('deskripsi')]);
        return redirect()->route('kamar.index')->with('success', 'Kamar berhasil diupdate.');
    }

    public function destroy($id)
    {
        $kamar = Kamar::findOrFail($id);
        $kamar->delete();
        return redirect()->route('kamar.index')->with('success', 'Kamar berhasil dihapus.');
    }
}
