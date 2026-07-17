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

@endsection
