<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Menggunakan Log::error

class NotificationController extends Controller
{
    /**
     * Menampilkan semua notifikasi (untuk halaman notifikasi).
     */
  public function index(Request $request)
{
    $user = Auth::user();
    
    // Tambahkan parameter untuk filter
    $filter = $request->get('filter', 'unread'); // default: unread
    
    $query = $user->notifications()->with('ticket');
    
    // Filter berdasarkan status baca
    if ($filter === 'unread') {
        $query->unread();
    } elseif ($filter === 'read') {
        $query->where('is_read', true);
    }
    // Jika 'all', tampilkan semua
    
    $notifications = $query->latest()->paginate(20);
    
    return view('notifications.index', compact('notifications', 'filter'));
}
    /**
     * Menangani klik notifikasi: menandai sudah dibaca dan redirect ke tiket.
     */
    public function show(Notification $notification)
    {
        // Pastikan user hanya bisa melihat notifikasinya sendiri
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to notification.');
        }

        // Tandai sebagai sudah dibaca (menggunakan method dari model)
        $notification->markAsRead();

        // Redirect ke detail ticket. Jika ticket_id null, redirect ke dashboard.
        if ($notification->ticket_id) {
            return redirect()->route('tickets.show', $notification->ticket_id);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Menandai semua notifikasi milik user sebagai sudah dibaca (Web Response).
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        // Menggunakan scope unread() yang ada di Model Notification
        $user->notifications()->unread()->update(['is_read' => true]);

        return redirect()->route('notifications.index')->with('success', 'Semua notifikasi telah ditandai sebagai sudah dibaca.');
    }

    /**
     * Menandai satu notifikasi sebagai sudah dibaca (API Response/AJAX).
     */


    /**
     * Mendapatkan jumlah notifikasi yang belum dibaca (untuk badge counter).
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            // Menggunakan relasi dan scope
            $count = $user->notifications()->unread()->count(); 
            
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            // Mengembalikan status kode 500 jika ada kesalahan server
            return response()->json(['count' => 0], 500); 
        }
    }

    /**
     * Mendapatkan daftar notifikasi yang belum dibaca (untuk dropdown),
     * menggunakan Accessor 'url' dan 'time_ago' dari model.
     */
    public function getUnreadNotifications()
    {
        try {
            $user = Auth::user();
            $notifications = $user->notifications()
                ->unread() // Menggunakan scope unread()
                ->with('ticket')
                ->latest()
                ->take(10)
                ->get();
            
            // Tidak perlu mapping manual (->map()) karena 'url' dan 'time_ago'
            // sudah otomatis ditambahkan sebagai Accessor di Model Notification.
            return response()->json($notifications);
        } catch (\Exception $e) {
            Log::error('Error getting unread notifications: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }


    public function markAsRead(Notification $notification)
{
    // Pastikan user hanya bisa mengupdate notifikasinya sendiri
    if ($notification->user_id !== Auth::id()) {
        if (request()->ajax()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        abort(403);
    }

    $notification->markAsRead();

    if (request()->ajax()) {
        return response()->json(['success' => true]);
    }

    return redirect()->route('notifications.index', ['filter' => 'unread'])
                    ->with('success', 'Notifikasi telah ditandai sebagai sudah dibaca.');
}
}


