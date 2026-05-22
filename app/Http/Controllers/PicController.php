<?php

namespace App\Http\Controllers;

use App\Models\Pic;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;



class PicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Cek authorization
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Jika AJAX request untuk DataTables
        if ($request->ajax()) {
            try {
                $pics = Pic::select('*');
                
                return DataTables::of($pics)
                    ->addIndexColumn()
                    ->addColumn('status', function($row) {
                        return $row->is_active ? 
                            '<span class="badge bg-success">Aktif</span>' : 
                            '<span class="badge bg-danger">Non-Aktif</span>';
                    })
                    ->addColumn('action', function($row) {
                        return '
                            <div class="btn-group">
                                <button class="btn btn-sm btn-warning btn-edit" data-id="'.$row->id.'">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'" data-name="'.$row->name.'">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        ';
                    })
                    ->rawColumns(['status', 'action'])
                    ->make(true);
                    
            } catch (\Exception $e) {
                \Log::error('PIC DataTables Error: ' . $e->getMessage());
                return response()->json(['error' => 'Server Error'], 500);
            }
        }

        // Jika bukan AJAX request, tampilkan view biasa
        return view('pics.index');
    }

    public function store(Request $request)
    {
        // Cek authorization
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        try {
            Pic::create([
                'name' => $request->name,
                'department' => $request->department,
                'is_active' => $request->boolean('is_active'), // Perbaikan di sini
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIC berhasil ditambahkan!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Store PIC Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan PIC: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        // Cek authorization
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $pic = Pic::findOrFail($id);
            return response()->json($pic);
        } catch (\Exception $e) {
            \Log::error('Show PIC Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        // Cek authorization
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        try {
            $pic = Pic::findOrFail($id);
            $pic->update([
                'name' => $request->name,
                'department' => $request->department,
                'is_active' => $request->boolean('is_active'), // Perbaikan di sini
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIC berhasil diupdate!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Update PIC Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate PIC: ' . $e->getMessage()
            ], 500);
        }
    }

   public function destroy($id)
{
    // Hanya admin yang boleh hapus
    if (!auth()->user()->isAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized action.'
        ], 403);
    }

    try {
        DB::beginTransaction();

        $pic = Pic::find($id);

        if (!$pic) {
            return response()->json([
                'success' => false,
                'message' => 'Data PIC tidak ditemukan'
            ], 404);
        }

        $picName = $pic->name;
        $pic->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'PIC "' . $picName . '" berhasil dihapus!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Delete PIC Error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus PIC: ' . $e->getMessage()
        ], 500);
    }
}

    public function getPics(Request $request)
    {
        // Method ini bisa diakses oleh semua user yang authenticated
        $pics = Pic::where('is_active', true)->get(); // Perbaikan scope
        return response()->json($pics);
    }

   // app/Http/Controllers/YourController.php

public function search_pic(Request $request)
{
    $query = $request->get('q');
    
    // Perbaikan: Hanya mencari berdasarkan 'name' ATAU 'department'
    $pics = \App\Models\Pic::where('name', 'like', "%$query%")
        ->orWhere('department', 'like', "%$query%") // Mencari juga di kolom department
        ->limit(10)
        // Pastikan Anda memilih semua kolom yang dibutuhkan di frontend
        ->get(['id', 'name', 'department']); 

    // Mengembalikan data dalam format JSON untuk AJAX
    return response()->json($pics);
}


}