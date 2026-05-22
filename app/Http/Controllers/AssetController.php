<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use App\Imports\AssetImport;
use Maatwebsite\Excel\Facades\Excel;


class AssetController extends Controller
{
   public function index()
{
    $assets = Asset::orderBy('fa_code')->get(); // gunakan nama jamak
    return view('assets.index', compact('assets'));
}



    public function create()
    {
        return view('assets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'nullable',
            'equip_tag' => 'nullable',
            'location' => 'nullable',
        ]);

        Asset::create($request->only('name', 'type', 'equip_tag', 'location'));
        return redirect()->route('assets.index')->with('success', 'Mesin berhasil ditambahkan.');
    }

  public function edit($id)
{
    $asset = Asset::findOrFail($id);
    return view('assets.edit', compact('asset'));
}


  public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'nullable',
        'equip_tag' => 'nullable',
        'location' => 'nullable',
    ]);

    $asset = Asset::findOrFail($id);
    $asset->update($request->only('name', 'type', 'equip_tag', 'location'));

    return redirect()->route('assets.index')->with('success', 'Data aset berhasil diperbarui.');
}


    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'FA Code berhasil dihapus.');
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $assets = Asset::where('fa_code', 'like', "%$query%")
            ->orWhere('name', 'like', "%$query%")
            ->limit(10)
            ->get(['id', 'fa_code', 'name']);

        return response()->json($assets);
    }
    public function import(Request $request) 
{
    $request->validate([
        'file_excel' => 'required|mimes:xlsx,xls,csv'
    ]);

    try {
        Excel::import(new AssetImport, $request->file('file_excel'));
        return back()->with('success', 'Data mesin berhasil di-import dari Excel!');
    } catch (\Exception $e) {
        return back()->with('error', 'Gagal import: ' . $e->getMessage());
    }
}
}
