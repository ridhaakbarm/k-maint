@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-bold">Tambah Mesin Baru</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('assets.store') }}" method="POST">
                @csrf
                
                {{-- Input Nama Aset --}}
                <div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Nama Mesin (Equipment)</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $asset->name ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Type</label>
        <input type="text" name="type" class="form-control" value="{{ old('type', $asset->type ?? '') }}" placeholder="Contoh: Extru/Mixer">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Equip Tag</label>
        <input type="text" name="equip_tag" class="form-control" value="{{ old('equip_tag', $asset->equip_tag ?? '') }}" placeholder="Contoh: Produksi/Lab">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Location</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $asset->location ?? '') }}" placeholder="Contoh: Produksi lt 1">
    </div>
</div>

                <div class="d-flex gap-2">
                    <a href="{{ route('assets.index') }}" class="btn btn-secondary px-4">Kembali</a>
                    <button type="submit" class="btn btn-primary px-4">Simpan Mesin</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection