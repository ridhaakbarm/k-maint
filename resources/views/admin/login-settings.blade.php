@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Pengaturan Halaman Login
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.login-settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Nama Perusahaan</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="{{ old('company_name', $settings->company_name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="portal_name" class="form-label">Nama Portal</label>
                                    <input type="text" class="form-control" id="portal_name" name="portal_name" 
                                           value="{{ old('portal_name', $settings->portal_name) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="footer_text" class="form-label">Teks Footer</label>
                            <input type="text" class="form-control" id="footer_text" name="footer_text" 
                                   value="{{ old('footer_text', $settings->footer_text) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="quote_text" class="form-label">Teks Quote</label>
                            <textarea class="form-control" id="quote_text" name="quote_text" rows="3" 
                                      required>{{ old('quote_text', $settings->quote_text) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="background_color" class="form-label">Warna Background</label>
                                    <input type="color" class="form-control form-control-color" id="background_color" 
                                           name="background_color" value="{{ old('background_color', $settings->background_color) }}" 
                                           title="Pilih warna background">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="background_image" class="form-label">Gambar Background</label>
                                    <input type="file" class="form-control" id="background_image" name="background_image" 
                                           accept="image/*">
                                    @if($settings->background_image)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="remove_background_image" 
                                               name="remove_background_image">
                                        <label class="form-check-label" for="remove_background_image">
                                            Hapus gambar background
                                        </label>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="show_password_toggle" 
                                           name="show_password_toggle" value="1" 
                                           {{ $settings->show_password_toggle ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_password_toggle">
                                        Tampilkan toggle password
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="show_quote" 
                                           name="show_quote" value="1" 
                                           {{ $settings->show_quote ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_quote">
                                        Tampilkan quote
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Pengaturan
                            </button>
                            <a href="{{ route('login') }}" target="_blank" class="btn btn-outline-secondary">
                                <i class="fas fa-eye me-2"></i>Preview Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

       <div class="col-md-4">
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">Preview Background</h6>
        </div>
        <div class="card-body">
            @if($settings->background_image && file_exists(public_path('login-backgrounds/' . $settings->background_image)))
            <img src="{{ asset('login-backgrounds/' . $settings->background_image) }}" 
                 alt="Background Preview" class="img-fluid rounded mb-3" style="max-height: 200px;">
            <div class="text-center">
                <small class="text-muted">{{ $settings->background_image }}</small>
            </div>
            @elseif($settings->background_image)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                File tidak ditemukan: {{ $settings->background_image }}
            </div>
            @endif
            
            <div class="p-3 rounded text-center mt-3" 
                 style="background-color: {{ $settings->background_color }}; min-height: 100px;">
                <small class="text-muted">Preview Warna Background</small>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
@endsection