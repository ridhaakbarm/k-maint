@extends('layouts.app')

@section('page_title', 'Edit Jadwal PM')
@section('breadcrumb', 'Edit Jadwal PM')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="card-title fw-bold mb-0">Edit Jadwal: {{ str_replace('FA - ', '', $pmSchedule->name) }}</h5>
            </div>
            <form action="{{ route('pm.schedule.update', $pmSchedule->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="card-body">
                    <div class="mb-3">
                        <label for="asset_id" class="form-label fw-bold">Mesin *</label>
                        <select class="form-select @error('asset_id') is-invalid @enderror"
                                id="asset_id" name="asset_id" required>
                            <option value="">-- Pilih Mesin --</option>
                            @foreach($machines as $machine)
                                <option value="{{ $machine->id }}"
                                    {{ (old('asset_id', $pmSchedule->asset_id) == $machine->id) ? 'selected' : '' }}>
                                    {{-- Menampilkan Nama Mesin saja --}}
                                    {{ $machine->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="schedule_type" class="form-label fw-bold">Tipe Jadwal *</label>
                        <select class="form-select @error('schedule_type') is-invalid @enderror" 
                                id="schedule_type" name="schedule_type" required>
                            <option value="">-- Pilih Tipe --</option>
                            @foreach($scheduleTypes as $key => $label)
                                <option value="{{ $key }}" 
                                    {{ (old('schedule_type', $pmSchedule->schedule_type) == $key) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- INPUT PIC BARU DI HALAMAN EDIT KAWAN --}}
<div class="mb-3">
    <label for="pic_name" class="form-label fw-bold">PIC (Penanggung Jawab MTC)</label>
    <select class="form-select select2 @error('pic_name') is-invalid @enderror" 
            id="pic_name" name="pic_name">
        <option value="">-- Pilih PIC (Opsional) --</option>
        @foreach($technicians as $tech)
            <option value="{{ $tech->name }}" 
                {{ (old('pic_name', $pmSchedule->pic_name) == $tech->name) ? 'selected' : '' }}>
                {{ $tech->name }} ({{ $tech->department }})
            </option>
        @endforeach
    </select>
    @error('pic_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">Teknisi ini yang akan bertanggung jawab rutin atas mesin ini kawan.</small>
</div>

                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" 
                               id="is_active" name="is_active" value="1" 
                               {{ (old('is_active', $pmSchedule->is_active)) ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">Status Jadwal Aktif</label>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end">
                    <a href="{{ route('pm.schedule.index') }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-save me-1"></i> Update Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Pilih PIC (Opsional) --',
        allowClear: true
    });
});
</script>
@endpush
@endsection