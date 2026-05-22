@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white fw-bold d-flex justify-content-between">
                <span><i class="fas fa-info-circle me-1"></i> Detail Template Checklist</span>
                <span class="badge bg-{{ $checklistTemplate->is_active ? 'success' : 'danger' }} text-uppercase">
                    {{ $checklistTemplate->is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%" class="bg-light">Jadwal PM (Aset)</th>
                        <td>
                            <strong>{{ $checklistTemplate->pmSchedule->asset->name ?? '-' }}</strong><br>
                            {{ $checklistTemplate->pmSchedule->name ?? '-' }}
                        </td>
                    </tr>
                    <tr><th class="bg-light">Item Checklist</th><td>{{ $checklistTemplate->item_name }}</td></tr>
                    <tr><th class="bg-light">Bagian yang Dicek</th><td>{{ $checklistTemplate->checked_part }}</td></tr>
                    <tr><th class="bg-light">Instruksi</th><td>{{ $checklistTemplate->instructions }}</td></tr>
                    <tr><th class="bg-light">Standar Pengecekan</th><td>{{ $checklistTemplate->check_standard }}</td></tr>
                    <tr><th class="bg-light">Jenis Pekerjaan</th><td>{{ $checklistTemplate->operation_source ?? '-' }}</td></tr>
                    <tr><th class="bg-light">Urutan</th><td>{{ $checklistTemplate->order }}</td></tr>
                    <tr>
                        <th class="bg-light">Minggu Aktif</th>
                        <td>
                            @if($checklistTemplate->active_weeks)
                                @foreach($checklistTemplate->active_weeks as $week)
                                    <span class="badge bg-outline-secondary border text-dark mb-1">W{{ $week }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Tidak ada jadwal minggu yang diatur</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('pm.templates.edit', $checklistTemplate->id) }}" class="btn btn-warning px-4">
                    <i class="fas fa-edit me-1"></i> Edit Template
                </a>
                <a href="{{ route('pm.templates.index') }}" class="btn btn-secondary px-4">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-3 text-center p-3">
            <h6 class="text-muted mb-2">Total Penggunaan</h6>
            <h2 class="fw-bold text-primary">{{ $checklistTemplate->checkItems()->count() }}</h2>
            <small class="text-muted">Kali digunakan dalam pengerjaan PM</small>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">Informasi Aset</div>
            <div class="card-body">
                <p class="mb-2"><strong>Nama Mesin:</strong> {{ $checklistTemplate->pmSchedule->asset->name ?? '-' }}</p>
                <p class="mb-0 small text-muted">ID Jadwal: #{{ $checklistTemplate->pm_schedule_id }}</p>
            </div>
        </div>
    </div>
</div>
@endsection