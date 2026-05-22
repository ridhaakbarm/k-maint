<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{


    public function index()
    {
        $vendors = Vendor::latest()->get();
        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_vendor' => 'required|string|max:255|unique:vendors',
            'nama_pt_cv' => 'required|string|max:255',
        ]);

        Vendor::create([
            'nama_vendor' => $request->nama_vendor,
            'nama_pt_cv' => $request->nama_pt_cv,
            'is_active' => true,
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor berhasil dibuat!');
    }

    public function show(Vendor $vendor)
    {
        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'nama_vendor' => 'required|string|max:255|unique:vendors,nama_vendor,' . $vendor->id,
            'nama_pt_cv' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $vendor->update([
            'nama_vendor' => $request->nama_vendor,
            'nama_pt_cv' => $request->nama_pt_cv,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor berhasil diupdate!');
    }

    public function destroy(Vendor $vendor)
    {
        // Cek jika vendor masih digunakan di tickets (jika ada relasi)
        // if ($vendor->tickets()->exists()) {
        //     return redirect()->route('vendors.index')->with('error', 'Tidak dapat menghapus vendor yang masih digunakan!');
        // }

        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor berhasil dihapus!');
    }


// app/Http/Controllers/VendorController.php



public function search_vendor(Request $request)
{
    $query = $request->get('q');

    $vendors = \App\Models\Vendor::where('nama_vendor', 'like', "%$query%")
        ->orWhere('nama_pt_cv', 'like', "%$query%")
        ->limit(10)
        ->get(['id', 'nama_vendor', 'nama_pt_cv']);

    return response()->json($vendors);
}





}