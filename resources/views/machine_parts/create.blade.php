@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Tambah Bagian Mesin Baru</h2>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('machine_parts.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="asset_id" class="form-label">Pilih Asset (Mesin)</label>
                    <select name="asset_id" id="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
                        <option value="">-- Pilih Mesin --</option>
                        @foreach($assets as $asset)
                            <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                {{ $asset->fa_code }} - {{ $asset->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Nama Bagian (Komponen)</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Motor Utama, Gearbox, V-Belt">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mt-4">
                    <a href="{{ route('machine_parts.index') }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">Simpan Bagian</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection