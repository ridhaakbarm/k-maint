<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LoginSettingController extends Controller
{
    public function edit()
    {
        $settings = LoginSetting::first();
        return view('admin.login-settings', compact('settings'));
    }

  public function update(Request $request)
{
    $settings = LoginSetting::first();
    
    $data = $request->validate([
        'company_name' => 'required|string|max:255',
        'portal_name' => 'required|string|max:255',
        'footer_text' => 'required|string|max:500',
        'quote_text' => 'required|string|max:500',
        'background_color' => 'required|string|max:7',
        'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
        'show_password_toggle' => 'boolean',
        'show_quote' => 'boolean',
    ]);

    // Handle background image upload - SIMPAN DI PUBLIC DIRECTORY
    if ($request->hasFile('background_image')) {
        $file = $request->file('background_image');
        
        // Pastikan file valid
        if ($file->isValid()) {
            // Delete old image jika ada
            if ($settings->background_image && file_exists(public_path('login-backgrounds/' . $settings->background_image))) {
                unlink(public_path('login-backgrounds/' . $settings->background_image));
            }
            
            // Buat directory jika belum ada
            $directory = public_path('login-backgrounds');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'login-bg-' . time() . '.' . $file->getClientOriginalExtension();
            
            // Pindahkan file ke public directory
            $file->move($directory, $filename);
            
            // Simpan hanya nama file (bukan full path)
            $data['background_image'] = $filename;
        }
    }

    // Handle remove background image
    if ($request->has('remove_background_image')) {
        if ($settings->background_image && file_exists(public_path('login-backgrounds/' . $settings->background_image))) {
            unlink(public_path('login-backgrounds/' . $settings->background_image));
        }
        $data['background_image'] = null;
    }

    // Handle checkbox values
    $data['show_password_toggle'] = $request->has('show_password_toggle');
    $data['show_quote'] = $request->has('show_quote');

    $settings->update($data);

    return redirect()->route('admin.login-settings.edit')
        ->with('success', 'Pengaturan login berhasil diperbarui!');
}
}