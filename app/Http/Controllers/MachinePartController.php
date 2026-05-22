<?php

namespace App\Http\Controllers;

use App\Models\MachinePart;
use App\Models\Asset;
use Illuminate\Http\Request;

class MachinePartController extends Controller
{
    public function index()
    {
        // Mengambil data bagian mesin beserta data asset-nya (Eager Loading)
        $machineParts = MachinePart::with('asset')->orderBy('asset_id')->get();
        return view('machine_parts.index', compact('machineParts'));
    }

    public function create()
    {
        // Kita butuh data asset untuk pilihan dropdown di form
        $assets = Asset::orderBy('fa_code')->get();
        return view('machine_parts.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'name' => 'required|string|max:255',
        ]);

        MachinePart::create($request->only('asset_id', 'name'));
        return redirect()->route('machine_parts.index')->with('success', 'Bagian mesin berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $machinePart = MachinePart::findOrFail($id);
        $assets = Asset::orderBy('fa_code')->get();
        return view('machine_parts.edit', compact('machinePart', 'assets'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'name' => 'required|string|max:255',
        ]);

        $machinePart = MachinePart::findOrFail($id);
        $machinePart->update($request->only('asset_id', 'name'));

        return redirect()->route('machine_parts.index')->with('success', 'Data bagian mesin berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $machinePart = MachinePart::findOrFail($id);
        $machinePart->delete();
        return redirect()->route('machine_parts.index')->with('success', 'Bagian mesin berhasil dihapus.');
    }
}