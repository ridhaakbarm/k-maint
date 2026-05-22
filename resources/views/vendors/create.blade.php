@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Vendor Baru</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Form Tambah Vendor</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('vendors.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_vendor" class="form-label">Nama PIC Vendor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_vendor') is-invalid @enderror" 
                               id="nama_vendor" name="nama_vendor" 
                               value="{{ old('nama_vendor') }}" required>
                        @error('nama_vendor')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_pt_cv" class="form-label">Nama PT/CV <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_pt_cv') is-invalid @enderror" 
                               id="nama_pt_cv" name="nama_pt_cv" 
                               value="{{ old('nama_pt_cv') }}" required>
                        @error('nama_pt_cv')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Vendor
                    </button>
                    <a href="{{ route('vendors.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection