@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold text-primary">Form Jadwal PM Baru</div>
            <form action="{{ route('pm.schedule.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Aset (Mesin) *</label>
                        <select class="form-select select2" name="asset_id" required>
                            {{-- Placeholder disederhanakan tanpa menyebut FA Code --}}
                            <option value="">-- Pilih Nama Mesin --</option>
                            @foreach($machines as $asset)
                                {{-- Hanya menampilkan Nama Aset --}}
                                <option value="{{ $asset->id }}">{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Jadwal *</label>
                        <select class="form-select" name="schedule_type" required>
                            @foreach($scheduleTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label text-dark" for="is_active">Status Aktif</label>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end">
                    <a href="{{ route('pm.schedule.index') }}" class="btn btn-secondary px-4">Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
