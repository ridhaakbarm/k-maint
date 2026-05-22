<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'username' => 'required|string|max:255|unique:users',
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'whatsapp' => 'required|string|max:20', // tambahkan validasi whatsapp
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
        'department' => 'required|string|max:255',
        'role' => 'required|in:admin,user,ga,mtc,manager',
    ]);

    User::create([
        'username' => $request->username,
        'name' => $request->name,
        'email' => $request->email,
        'whatsapp' => $request->whatsapp, // tambahkan ini
        'password' => Hash::make($request->password),
        'department' => $request->department,
        'role' => $request->role,
    ]);

    return redirect()->route('users.index')->with('success', 'User berhasil dibuat!');
}

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

   public function update(Request $request, User $user)
{
    $request->validate([
        'username' => 'required|string|max:255|unique:users,username,' . $user->id,
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'whatsapp' => 'required|string|max:20', // tambahkan validasi whatsapp
        'department' => 'required|string|max:255',
        'role' => 'required|in:admin,user,ga,mtc',
        'password' => 'nullable|confirmed|min:8',
    ]);

    $data = [
        'username' => $request->username,
        'name' => $request->name,
        'email' => $request->email,
        'whatsapp' => $request->whatsapp, // tambahkan ini
        'department' => $request->department,
        'role' => $request->role,
    ];

    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);

    return redirect()->route('users.index')->with('success', 'User berhasil diupdate!');
}
    public function destroy(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Tidak dapat menghapus akun sendiri!');
        }

        // Collect information about what will be deleted
        $deletedInfo = [];

        // 1. Delete technician attendances (clock in/out records)
        if ($user->technicianAttendances()->exists()) {
            $count = $user->technicianAttendances()->count();
            $user->technicianAttendances()->delete();
            $deletedInfo[] = "Data Clock In/Out: {$count} data";
        }

        // 2. Delete PM checks where user is technician
        if ($user->pmChecksAsTechnician()->exists()) {
            $count = $user->pmChecksAsTechnician()->count();
            $user->pmChecksAsTechnician()->delete();
            $deletedInfo[] = "Pengerjaan PM (sebagai teknisi): {$count} data";
        }

        // 3. Delete PM checks where user is admin
        if ($user->pmChecksAsAdmin()->exists()) {
            $count = $user->pmChecksAsAdmin()->count();
            $user->pmChecksAsAdmin()->delete();
            $deletedInfo[] = "Pengerjaan PM (sebagai admin): {$count} data";
        }

        // 4. Delete ticket notes (catatan/obrolan tiket)
        if ($user->ticketNotes()->exists()) {
            $count = $user->ticketNotes()->count();
            $user->ticketNotes()->delete();
            $deletedInfo[] = "Catatan Tiket: {$count} data";
        }

        // 5. Update tickets - set requester_id to NULL or reassign to admin
        if ($user->tickets()->exists()) {
            $count = $user->tickets()->count();
            // Reassign to admin (id = 1) instead of deleting
            $adminId = \App\Models\User::where('role', 'admin')->first()?->id;
            $user->tickets()->update(['requester_id' => $adminId]);
            $deletedInfo[] = "Tiket: {$count} data (reassigned ke Admin)";
        }

        // 6. Finally, delete the user
        $user->delete();

        // Show success message with what was deleted
        $message = 'User berhasil dihapus!';
        if (!empty($deletedInfo)) {
            $deletedList = implode('<br>• ', $deletedInfo);
            $message .= "<br><br><small>Data yang dihapus:<br>• {$deletedList}</small>";
        }

        return redirect()->route('users.index')->with('success', $message);
    }
}