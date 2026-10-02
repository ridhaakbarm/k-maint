@extends('layouts.app')

@section('page_title', 'Detail Checklist PM')
@section('breadcrumb', 'Detail Checklist PM')

@push('css')
{{-- Select2 untuk fitur pencarian di dropdown yang banyak --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* ========== TABLET OPTIMIZATION ========== */
    
    /* Global Font & Spacing */
    body { 
        font-size: 1rem; 
        line-height: 1.5;
    }
    
    /* Card Improvements */
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    .card-header {
        border-radius: 0 !important;
    }
    
    /* Button Sizing for Tablet Touch */
    .btn-xs { 
        padding: 4px 8px; 
        font-size: 0.85rem; 
        border-radius: 6px; 
    }
    
    .btn-lg {
        padding: 12px 24px;
        font-size: 1.1rem;
        border-radius: 8px;
        min-width: 150px;
    }
    
    /* Table Styling - FIXED LAYOUT */
    .table {
        font-size: 0.9rem;
        table-layout: fixed;
        width: 100%;
    }
    
    .table th { 
        background-color: #f8f9fa !important; 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 0.75rem;
        vertical-align: middle;
        padding: 10px 6px;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
    }
    
    .table td {
        vertical-align: middle;
        padding: 10px 6px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    /* Select2 Tablet Optimization - FIXED */
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 0.95rem !important;
        min-height: 38px !important;
        padding: 6px 10px !important;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding: 0 !important;
        line-height: 26px !important;
    }
    
    .select2-container--bootstrap-5 .select2-dropdown {
        font-size: 0.95rem !important;
        z-index: 9999 !important;
    }
    
    .select2-results__option {
        padding: 10px 12px !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection--single {
        height: 42px !important;
        min-height: 42px !important;
        display: block !important;
        padding: 6px 34px 6px 10px !important;
        overflow: hidden !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        display: block !important;
        width: 100% !important;
        line-height: 28px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        padding-right: 0 !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        top: 50% !important;
        transform: translateY(-50%);
        right: 8px !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection__clear {
        margin-right: 18px !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection--multiple {
        min-height: 42px !important;
        padding: 4px 8px !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 3px !important;
    }

    .pm-result-cell .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        font-size: 0.75rem !important;
        max-width: 100% !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .column-note {
        display: block;
        margin-top: 3px;
        color: #6c757d;
        font-size: 0.6rem;
        font-weight: 600;
        line-height: 1;
        text-transform: none;
        white-space: nowrap;
    }
    
    /* Ensure dropdown doesn't overflow */
    .table-responsive {
        overflow-x: auto;
        overflow-y: visible;
    }
    
    /* Fix select2 dropdown position in table */
    .select2-container {
        z-index: 1056 !important;
    }
    
    .select2-dropdown {
        z-index: 9999 !important;
    }
    
    /* Make sure tbody doesn't clip dropdown */
    tbody tr {
        position: relative;
    }

    /* Item Name & Part - Balanced Size */
    .custom-item-name {
        font-size: 1rem !important; 
        font-weight: 700;
        line-height: 1.3;
        color: #0d6efd;
        margin-bottom: 6px;
        display: block;
    }

    .custom-part-detail {
        font-size: 0.85rem !important; 
        color: #6c757d;
        font-weight: 600;
        display: block;
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
        margin-top: 6px;
    }
    
    /* Instructions Box - Compact */
    .instruction-box {
        font-size: 0.85rem;
        line-height: 1.5;
        background: #fff3cd;
        padding: 8px 10px;
        border-radius: 6px;
        border-left: 3px solid #ffc107;
        margin-bottom: 6px;
    }
    
    .standard-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        background: #e7f1ff;
        color: #084298;
        border: 1px solid #b6d4fe;
        border-radius: 4px;
        display: inline-block;
    }

    /* Info Record Styles - Compact */
    .time-record { 
        font-size: 0.85rem; 
        color: #198754; 
        font-weight: 700;
        display: block;
        margin-bottom: 3px;
    }
    
    .user-record { 
        font-size: 0.75rem; 
        color: #6c757d; 
        font-weight: 600;
        display: block;
    }

    /* Textarea Optimization - Compact */
    .form-control {
        font-size: 0.9rem !important;
        padding: 8px 10px !important;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        line-height: 1.4 !important;
    }
    
    .form-control:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15) !important;
    }
    
    textarea.form-control {
        min-height: 80px !important;
        resize: vertical;
    }

    /* Camera Input - Compact but Touch-Friendly */
    .camera-input {
        display: none !important;
    }

    .camera-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        padding: 12px 8px;
        border-radius: 8px;
        cursor: pointer;
        width: 100%;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        min-height: 70px;
    }

    .camera-label:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        background: linear-gradient(135deg, #495057 0%, #212529 100%);
    }

    .camera-label i {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }
    
    .camera-label span {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    /* Foto Berhasil Diambil */
    .camera-filled {
        background: linear-gradient(135deg, #198754 0%, #0d6832 100%) !important;
        border-color: #0d6832 !important;
    }
    
    .photo-success {
        color: #198754;
        font-weight: 700;
        font-size: 0.75rem;
        margin-top: 6px;
    }
    
    /* View Photo Button - Compact */
    .btn-view-photo {
        background: #0dcaf0;
        color: #000;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8rem;
        width: 100%;
        margin-bottom: 8px;
        transition: all 0.3s;
    }
    
    .btn-view-photo:hover {
        background: #31d2f2;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(13, 202, 240, 0.3);
    }

    /* Badge & Alert Improvements */
    .badge { 
        font-size: 0.85rem; 
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
    }
    
    .alert {
        border-radius: 10px;
        border: none;
        padding: 16px 20px;
        font-size: 1rem;
    }
    
    .alert strong { 
        font-size: 1.1rem; 
    }
    
    /* Header Info Cards */
    .info-card {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        border: 1px solid #dee2e6;
    }
    
    .info-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 600;
        margin-bottom: 4px;
        display: block;
    }
    
    .info-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #212529;
        display: block;
    }
    
    /* Verification Switch - Touch-Friendly but Compact */
    .form-check-input {
        width: 2.8em !important;
        height: 1.4em !important;
        cursor: pointer;
        border-width: 1px !important;
    }
    
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    
    /* Action Buttons Container */
    .action-buttons {
        background: #f8f9fa;
        padding: 16px;
        border-radius: 8px;
        margin-top: 20px;
        border: 1px solid #dee2e6;
    }
    
    /* Status Badge Improvements */
    .status-badge-large {
        font-size: 0.95rem;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    
    /* Row Number Circle - Compact */
    .row-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: #0d6efd;
        color: white;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .btn-lg {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .custom-item-name {
            font-size: 1.2rem !important;
        }
        
        .custom-part-detail {
            font-size: 1rem !important;
        }
    }
    
    /* Smooth Transitions */
    .form-select, .form-control, .btn {
        transition: all 0.2s ease;
    }
    
    /* Loading State */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Read-Only Mode Styles */
    .form-readonly .form-control,
    .form-readonly .form-select,
    .form-readonly .camera-label {
        pointer-events: none;
        background-color: #f8f9fa !important;
        opacity: 0.7;
    }

    .form-readonly .camera-label {
        cursor: not-allowed !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                {{-- Header --}}
                <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0 fw-bold text-white">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Pengerjaan PM: {{ $pmCheck->pmSchedule->asset->name ?? 'N/A' }}
                        </h5>
                        <span class="status-badge-large bg-light text-dark">
                            {{ strtoupper(str_replace('_', ' ', $pmCheck->status)) }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    {{-- Header Info Cards --}}
                    <div class="row mb-4">
                        <div class="col-md-3 col-6">
                            <div class="info-card">
                                <span class="info-label">
                                    <i class="fas fa-cog me-1"></i>Mesin / Aset
                                </span>
                                <span class="info-value">{{ $pmCheck->pmSchedule->asset->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-card">
                                <span class="info-label">
                                    <i class="fas fa-calendar me-1"></i>Tanggal
                                </span>
                                <span class="info-value">{{ $pmCheck->check_date ? $pmCheck->check_date->format('d/m/Y') : '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-card">
                                <span class="info-label">
                                    <i class="fas fa-user-clock me-1"></i>Shift / Teknisi
                                </span>
                                <span class="info-value">{{ $pmCheck->shift ?? '-' }} / {{ $pmCheck->technician_name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-card">
                                <span class="info-label">
                                    <i class="fas fa-user-check me-1"></i>Verifikasi Akhir
                                </span>
                                <span class="info-value text-primary">{{ $pmCheck->admin->name ?? 'Menunggu' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Success Message --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Berhasil!</strong> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Read-Only Mode Warning untuk MTC --}}
                    @if($readOnly ?? false)
                        <div class="alert alert-info alert-dismissible fade show shadow-sm">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-eye me-3 fa-2x"></i>
                                <div>
                                    <strong>MODE LIHAT SAJA (Read-Only)</strong>
                                    <p class="mb-0 mt-1">
                                        Anda sedang melihat checklist untuk <strong>Week {{ $pmCheck->week_number }}</strong>
                                        yang bukan week yang sedang berjalan (Week {{ now()->weekOfYear }}).
                                    </p>
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Sebagai teknisi MTC, Anda hanya dapat melihat data ini tetapi tidak dapat mengubahnya.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Status Rekam Aktivitas --}}
                    @if($pmCheck->status == 'in_progress')
                        @php
                            $isRecording = \App\Models\TechnicianActivity::where('user_id', Auth::id())
                                ->where('category', 'PM')->where('reference_id', $pmCheck->id)
                                ->where('status', 'running')->exists();
                        @endphp

                        @if(!$isRecording)
                            <div class="alert alert-warning shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-pause-circle me-2 fa-lg"></i>
                                    <strong>Status: Paused.</strong> Aktivitas belum terekam di Monitoring Board.
                                </div>
                                <form action="{{ route('pm.execution.startWork', $pmCheck->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-warning fw-bold shadow-sm">
                                        <i class="fas fa-play me-2"></i>LANJUTKAN REKAM
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-success shadow-sm">
                                <i class="fas fa-running me-2 fa-lg"></i>
                                <strong>Status: Running.</strong> Pengerjaan sedang direkam secara real-time.
                            </div>
                        @endif
                    @endif

                    {{-- Main Form --}}
                    <form action="{{ route('pm.execution.batch-update', $pmCheck->id) }}" method="POST" id="batchUpdateForm" enctype="multipart/form-data" class="{{ ($readOnly ?? false) ? 'form-readonly' : '' }}">
                        @csrf
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover align-middle" style="min-width: 1400px;">
                                <thead class="text-center">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 180px;">Item & Bagian</th>
                                        <th style="width: 200px;">Instruksi</th>
                                        <th style="width: 220px;">Hasil</th>
                                        <th style="width: 130px;">Waktu & User</th>
                                        <th style="width: 220px;">
                                            Tindakan / Keterangan
                                            <span class="column-note">* Keterangan = Hasil Pengecekan</span>
                                        </th>
                                        <th style="width: 200px;">Selanjutnya (Next)</th>
                                        <th style="width: 140px;">Foto</th>
                                        <th style="width: 70px;">
                                            Verif
                                            @if(Auth::user()->isAdmin())
                                                <div class="form-check form-switch d-inline-block mt-1">
                                                    <input class="form-check-input" type="checkbox" id="checkAllVerif">
                                                </div>
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pmCheck->checkItems as $index => $item)
                                    <tr id="item-row-{{ $item->id }}">
                                        {{-- No --}}
                                        <td class="text-center">
                                            <span class="row-number">{{ $index + 1 }}</span>
                                        </td>

                                        {{-- Item & Part --}}
                                        <td>
                                            <div class="custom-item-name">
                                                {{ $item->checklistTemplate->item_name }}
                                                @if($item->checklistTemplate->frequency_label === 'bi-weekly')
                                                    <span class="badge bg-secondary ms-1" style="font-size:0.7rem;"><i class="fas fa-calendar-week me-1"></i>Bi-Weekly</span>
                                                @elseif($item->checklistTemplate->frequency_label === 'monthly')
                                                    <span class="badge bg-info text-dark ms-1" style="font-size:0.7rem;"><i class="fas fa-calendar-alt me-1"></i>Monthly</span>
                                                @elseif($item->checklistTemplate->frequency_label === 'quarterly')
                                                    <span class="badge bg-danger ms-1" style="font-size:0.7rem;"><i class="fas fa-calendar me-1"></i>Quarterly</span>
                                                @elseif($item->checklistTemplate->frequency_label === 'yearly')
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.7rem;"><i class="fas fa-calendar-check me-1"></i>Yearly</span>
                                                @endif
                                            </div>
                                            <div class="custom-part-detail">
                                                <i class="fas fa-puzzle-piece me-1"></i>
                                                {{ $item->checklistTemplate->checked_part }}
                                            </div>
                                        </td>

                                        {{-- Instructions --}}
                                        <td>
                                            <div class="instruction-box">
                                                {{ $item->checklistTemplate->instructions }}
                                            </div>
                                            <div class="standard-badge mb-1">
                                                <i class="fas fa-power-off me-1"></i>
                                                Kondisi: {{ $item->checklistTemplate->inspection_condition ?? '-' }}
                                            </div>
                                            <div class="standard-badge">
                                                <i class="fas fa-ruler me-1"></i>
                                                Std: {{ $item->checklistTemplate->check_standard }}
                                            </div>
                                        </td>

                                        {{-- Condition Result --}}
                                        <td class="pm-result-cell">
                                            @if($pmCheck->status == 'in_progress' && (Auth::user()->isMTC() || Auth::user()->isAdmin() || Auth::user()->isGA()))
                                                @php
                                                    $predefinedConditions = ['Berfungsi','Normal','Bersih','Tekanan OK','Aus','Bergetar','Bocor','Kendor','Kering','Kotor','Rantas','Rompal','Rusak','Suara Kasar','Tindakan Tidak OK','Sedang dalam perbaikan'];
                                                    // Pecah simpanan lama (comma-separated) jadi pilihan baku + custom manual
                                                    $storedParts = !empty($item->condition) ? array_map('trim', explode(',', $item->condition)) : [];
                                                    $storedParts = array_values(array_filter($storedParts, fn($v) => $v !== '' && $v !== 'Other' && $v !== 'Tulis Kondisinya'));
                                                    $selectedPredefined = array_values(array_filter($storedParts, fn($v) => in_array($v, $predefinedConditions)));
                                                    $customValues = array_values(array_filter($storedParts, fn($v) => !in_array($v, $predefinedConditions)));
                                                    $isOtherSelected = !empty($customValues) || (!empty($item->condition) && str_contains($item->condition, 'Other'));
                                                    $otherValues = !empty($customValues) ? $customValues : [''];
                                                @endphp
                                                <select name="items[{{ $item->id }}][condition][]"
                                                        multiple="multiple"
                                                        class="form-select condition-select select2-searchable"
                                                        id="condition-{{ $item->id }}"
                                                        data-item-id="{{ $item->id }}"
                                                        data-placeholder="-- Pilih Hasil (bisa >1) --">

                                                    <optgroup label="Kondisi Normal">
                                                        <option value="Berfungsi" {{ in_array('Berfungsi', $selectedPredefined) ? 'selected' : '' }}>Berfungsi</option>
                                                        <option value="Normal" {{ in_array('Normal', $selectedPredefined) ? 'selected' : '' }}>Normal</option>
                                                        <option value="Bersih" {{ in_array('Bersih', $selectedPredefined) ? 'selected' : '' }}>Bersih</option>
                                                        <option value="Tekanan OK" {{ in_array('Tekanan OK', $selectedPredefined) ? 'selected' : '' }}>Tekanan OK</option>
                                                    </optgroup>

                                                    <optgroup label="Temuan Masalah">
                                                        <option value="Aus" {{ in_array('Aus', $selectedPredefined) ? 'selected' : '' }}>Aus</option>
                                                        <option value="Bergetar" {{ in_array('Bergetar', $selectedPredefined) ? 'selected' : '' }}>Bergetar</option>
                                                        <option value="Bocor" {{ in_array('Bocor', $selectedPredefined) ? 'selected' : '' }}>Bocor</option>
                                                        <option value="Kendor" {{ in_array('Kendor', $selectedPredefined) ? 'selected' : '' }}>Kendor</option>
                                                        <option value="Kering" {{ in_array('Kering', $selectedPredefined) ? 'selected' : '' }}>Kering</option>
                                                        <option value="Kotor" {{ in_array('Kotor', $selectedPredefined) ? 'selected' : '' }}>Kotor</option>
                                                        <option value="Rantas" {{ in_array('Rantas', $selectedPredefined) ? 'selected' : '' }}>Rantas</option>
                                                        <option value="Rompal" {{ in_array('Rompal', $selectedPredefined) ? 'selected' : '' }}>Rompal</option>
                                                        <option value="Rusak" {{ in_array('Rusak', $selectedPredefined) ? 'selected' : '' }}>Rusak</option>
                                                        <option value="Suara Kasar" {{ in_array('Suara Kasar', $selectedPredefined) ? 'selected' : '' }}>Suara Kasar</option>
                                                        <option value="Tindakan Tidak OK" {{ in_array('Tindakan Tidak OK', $selectedPredefined) ? 'selected' : '' }}>Tindakan Tidak OK</option>
                                                        <option value="Sedang dalam perbaikan" {{ in_array('Sedang dalam perbaikan', $selectedPredefined) ? 'selected' : '' }}>Sedang dalam perbaikan</option>
                                                    </optgroup>

                                                    <option value="Other" {{ $isOtherSelected ? 'selected' : '' }}>Other</option>
                                                </select>
                                                <small class="text-muted d-block mt-1" style="font-size:0.7rem;"><i class="fas fa-info-circle me-1"></i>Bisa pilih lebih dari 1. Centang "Other" untuk tambah tulisan manual.</small>

                                                {{-- Input manual dinamis untuk pilihan Other (bisa digabung dengan pilihan di atas) --}}
                                                <div class="other-input-wrapper mt-2 p-2 border rounded bg-light" id="other-wrapper-{{ $item->id }}" style="{{ $isOtherSelected ? '' : 'display:none;' }}">
                                                    
                                                    <div class="other-inputs-list" id="other-list-{{ $item->id }}">
                                                        @foreach($otherValues as $otherVal)
                                                        <div class="input-group input-group-sm mb-1 other-input-row">
                                                            <input type="text"
                                                                   name="items[{{ $item->id }}][condition_others][]"
                                                                   class="form-control other-manual-input"
                                                                   placeholder="Ketik hasil manual..."
                                                                   value="{{ $otherVal }}">
                                                            <button type="button" class="btn btn-outline-danger btn-remove-other" title="Hapus input ini">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-1 btn-add-other" data-item="{{ $item->id }}">
                                                        <i class="fas fa-plus me-1"></i>Tambah Input
                                                    </button>
                                                </div>
                                            @else
                                                @php
                                                    $readParts = !empty($item->condition) ? array_map('trim', explode(',', $item->condition)) : [];
                                                    $readParts = array_values(array_filter($readParts, fn($v) => $v !== ''));
                                                @endphp
                                                @if(empty($readParts))
                                                    <span class="badge bg-secondary text-white">Belum Dicek</span>
                                                @else
                                                    @foreach($readParts as $part)
                                                        <span class="badge {{ in_array($part, ['Normal', 'Berfungsi', 'Bersih', 'Tekanan OK']) ? 'bg-success' : 'bg-secondary' }} text-white me-1 mb-1" style="display:inline-block;">
                                                            {{ $part }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </td>

                                        {{-- Time & User --}}
                                        <td class="text-center bg-light">
                                            @if($item->checked_at)
                                                <div class="time-record">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $item->checked_at->format('d/m/y H:i') }}
                                                </div>
                                                <div class="user-record">
                                                    {{ $item->checkedBy->name ?? 'System' }}
                                                </div>
                                            @else
                                                <span class="text-muted fst-italic">Menunggu...</span>
                                            @endif
                                        </td>

                                        {{-- Action Taken --}}
                                        <td>
                                            <textarea 
                                                name="items[{{ $item->id }}][action_taken]" 
                                                class="form-control" 
                                                rows="3" 
                                                placeholder="Tindakan atau keterangan hasil pengecekan..."
                                                {{ ($pmCheck->status != 'in_progress' && !Auth::user()->isAdmin()) ? 'disabled' : '' }}
                                            >{{ $item->action_taken }}</textarea>
                                        </td>

                                        {{-- Next Action --}}
                                        <td>
                                            <textarea 
                                                name="items[{{ $item->id }}][next_action]" 
                                                class="form-control" 
                                                rows="3" 
                                                placeholder="Tindakan selanjutnya..."
                                                {{ ($pmCheck->status != 'in_progress' && !Auth::user()->isAdmin()) ? 'disabled' : '' }}
                                            >{{ $item->next_action }}</textarea>
                                        </td>

                                        {{-- Photo --}}
                                        <td class="text-center">
                                            {{-- View Photo Button --}}
                                            @if($item->photo_before)
                                                <a href="{{ Storage::disk('s3')->url($item->photo_before) }}" 
                                                   target="_blank" 
                                                   class="btn-view-photo">
                                                    <i class="fas fa-eye me-1"></i>LIHAT FOTO
                                                </a>
                                            @endif

                                            {{-- Camera Input --}}
                                            @if($pmCheck->status == 'in_progress' && (Auth::user()->isMTC() || Auth::user()->isAdmin() || Auth::user()->isGA()))
                                                <label for="camera-{{ $item->id }}" 
                                                       class="camera-label" 
                                                       id="label-{{ $item->id }}">
                                                    <i class="fas fa-camera"></i>
                                                    <span>AMBIL FOTO</span>
                                                </label>
                                                
                                                <input type="file" 
                                                       name="items[{{ $item->id }}][photo_before]" 
                                                       id="camera-{{ $item->id }}" 
                                                       class="camera-input camera-trigger" 
                                                       accept="image/*" 
                                                       capture="environment">
                                                
                                                <div id="status-{{ $item->id }}" class="photo-success d-none">
                                                    <i class="fas fa-check-circle"></i> FOTO SIAP
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Verification --}}
                                        <td class="text-center">
                                            @if(Auth::user()->isAdmin())
                                                <div class="form-check form-switch d-inline-block">
                                                    <input type="hidden" name="items[{{ $item->id }}][is_verified]" value="0">
                                                    <input class="form-check-input verif-checkbox" 
                                                           type="checkbox" 
                                                           name="items[{{ $item->id }}][is_verified]" 
                                                           value="1" 
                                                           {{ $item->verified_by_user_id ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                <i class="fas {{ $item->verified_by_user_id ? 'fa-check-circle text-success' : 'fa-clock text-muted' }} fa-2x"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>

                                    {{-- Action Buttons --}}
                    <div class="action-buttons">
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <a href="{{ route('pm.execution.index', ['scheduleType' => $pmCheck->pmSchedule->schedule_type ?? 'weekly', 'week' => $pmCheck->week_number, 'date' => optional($pmCheck->check_date)->toDateString()]) }}" class="btn btn-secondary btn-lg w-100">
                                    <i class="fas fa-arrow-left me-2"></i>KEMBALI
                                </a>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    @if($readOnly ?? false)
                                        {{-- READ-ONLY MODE: Tampilkan tombol info saja --}}
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-eye me-2"></i>
                                            <strong>MODE BACA SAJA</strong> - Anda tidak dapat mengubah checklist ini
                                        </div>
                                    @else
                                        @if($pmCheck->status != 'completed')
                                            <button type="submit"
                                                    class="btn btn-primary btn-lg shadow"
                                                    form="batchUpdateForm"
                                                    id="saveButton">
                                                <i class="fas fa-save me-2"></i>SIMPAN PERUBAHAN
                                            </button>
                                        @endif

                                        @if($pmCheck->status == 'in_progress' && (Auth::user()->isMTC() || Auth::user()->isAdmin() || Auth::user()->isGA()))
                                            <form method="POST"
                                                  action="{{ route('pm-checks.complete', $pmCheck->id) }}"
                                                  class="d-inline"
                                                  onsubmit="return validateBeforeComplete()">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-lg shadow">
                                                    <i class="fas fa-check-double me-2"></i>SELESAIKAN
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    @if(Auth::user()->isAdmin() && $pmCheck->status == 'waiting_verification')
                                        <button type="submit" 
                                                class="btn btn-danger btn-lg shadow" 
                                                form="batchUpdateForm"
                                                name="final_verify"
                                                value="1"
                                                id="btnFinalVerify" 
                                                disabled>
                                            <i class="fas fa-certificate me-2"></i>VERIFIKASI AKHIR
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // ========== 1. Select2 Initialization (multi-pilih hasil) ==========
    $('.select2-searchable').each(function() {
        const ph = $(this).data('placeholder') || '-- Pilih Hasil (bisa >1) --';
        $(this).select2({
            theme: 'bootstrap-5',
            placeholder: ph,
            allowClear: true,
            width: '100%',
            closeOnSelect: false,
            dropdownParent: $('body'),
            language: {
                noResults: function() {
                    return "Tidak ditemukan";
                },
                searching: function() {
                    return "Mencari...";
                }
            }
        });
    });

    // ========== 1b. Other (manual input dinamis, bisa gabung dgn pilihan) ==========
    function toggleOtherWrapper(itemId, focusManual) {
        let val = $('#condition-' + itemId).val();
        if (val === null || val === undefined) val = [];
        if (!Array.isArray(val)) val = [val];
        const wrapper = $('#other-wrapper-' + itemId);
        // Tampilkan jika 'Other' dicentang; tetap tampil jika sudah ada isi manual lama
        const hasManual = $('#other-list-' + itemId + ' .other-manual-input').filter(function() { return $(this).val().trim() !== ''; }).length > 0;
        if (val.includes('Other') || hasManual) {
            wrapper.stop(true, true).slideDown(150, function() {
                if (focusManual) {
                    const target = $('#other-list-' + itemId + ' .other-manual-input').filter(function() { return $(this).val().trim() === ''; }).first();
                    (target.length ? target : $('#other-list-' + itemId + ' .other-manual-input').last()).focus();
                }
            });
        } else {
            wrapper.stop(true, true).slideUp(150);
        }
    }

    // Init saat load (untuk data custom lama)
    $('.condition-select').each(function() {
        const itemId = $(this).data('item-id') || $(this).attr('id').split('-')[1];
        toggleOtherWrapper(itemId, false);
    });

    // Saat dropdown berubah (change biasa)
    $(document).on('change', '.condition-select', function() {
        const itemId = $(this).data('item-id') || $(this).attr('id').split('-')[1];
        toggleOtherWrapper(itemId, false);
    });

    // Saat pilih via Select2: kalau yang dipilih "Other", langsung tutup dropdown
    // supaya input manual di bawahnya tidak tertutup dropdown dan langsung bisa diketik.
    $(document).on('select2:select', '.condition-select', function(e) {
        const itemId = $(this).data('item-id') || $(this).attr('id').split('-')[1];
        if (e.params && e.params.data && e.params.data.id === 'Other') {
            $(this).select2('close');
            toggleOtherWrapper(itemId, true);
        } else {
            toggleOtherWrapper(itemId, false);
        }
    });

    $(document).on('select2:unselect', '.condition-select', function() {
        const itemId = $(this).data('item-id') || $(this).attr('id').split('-')[1];
        toggleOtherWrapper(itemId, false);
    });

    // Tambah input manual baru
    $(document).on('click', '.btn-add-other', function() {
        const itemId = $(this).data('item');
        const list = $('#other-list-' + itemId);
        const row = `
            <div class="input-group input-group-sm mb-1 other-input-row">
                <input type="text"
                       name="items[${itemId}][condition_others][]"
                       class="form-control other-manual-input"
                       placeholder="Ketik hasil manual...">
                <button type="button" class="btn btn-outline-danger btn-remove-other" title="Hapus input ini">
                    <i class="fas fa-minus"></i>
                </button>
            </div>`;
        list.append(row);
        list.find('input:last').focus();
        formChanged = true;
    });

    // Hapus satu baris input manual (sisakan minimal 1)
    $(document).on('click', '.btn-remove-other', function() {
        const list = $(this).closest('.other-inputs-list');
        if (list.find('.other-input-row').length > 1) {
            $(this).closest('.other-input-row').remove();
        } else {
            list.find('input').val('').focus();
        }
        formChanged = true;
    });

    // ========== 2. Master Switch Verifikasi ==========
    $('#checkAllVerif').on('change', function() {
        $('.verif-checkbox').prop('checked', this.checked).trigger('change');
    });

    $('.verif-checkbox').on('change', function() {
        // Uncheck master if any child is unchecked
        if (!this.checked) {
            $('#checkAllVerif').prop('checked', false);
        }
        checkFinalVerifyStatus();
    });

    // ========== 3. Final Verification Button Control ==========
    function checkFinalVerifyStatus() {
        const total = $('.verif-checkbox').length;
        const checked = $('.verif-checkbox:checked').length;
        const allVerified = (total > 0 && total === checked);
        
        $('#btnFinalVerify').prop('disabled', !allVerified);
        
        if (allVerified) {
            $('#btnFinalVerify').removeClass('btn-secondary').addClass('btn-danger');
        } else {
            $('#btnFinalVerify').removeClass('btn-danger').addClass('btn-secondary');
        }
    }

    // ========== 4. Camera Input Handler ==========
    $('.camera-trigger').on('change', function() {
        const id = $(this).attr('id').split('-')[1];
        const label = $(`#label-${id}`);
        const status = $(`#status-${id}`);
        
        if (this.files && this.files.length > 0) {
            // Update UI to show photo is ready
            label.addClass('camera-filled');
            label.html('<i class="fas fa-sync-alt"></i><span>GANTI FOTO</span>');
            status.removeClass('d-none');
            
            // Optional: Show file name
            const fileName = this.files[0].name;
            console.log(`Foto berhasil dipilih: ${fileName}`);
        }
    });

    // ========== 5. Auto-save Notification ==========
    let saveTimeout;
    $(document).on('change', 'textarea, .condition-select, .verif-checkbox, .other-manual-input', function() {
        clearTimeout(saveTimeout);
        const saveBtn = $('#saveButton');
        if (!saveBtn.length) return;
        
        // Show unsaved changes indicator
        if (!saveBtn.hasClass('btn-warning')) {
            saveBtn.addClass('btn-warning').removeClass('btn-primary');
            saveBtn.html('<i class="fas fa-exclamation-triangle me-2"></i>ADA PERUBAHAN - SIMPAN');
        }
        
        // Auto-reset after 3 seconds
        saveTimeout = setTimeout(function() {
            saveBtn.removeClass('btn-warning').addClass('btn-primary');
            saveBtn.html('<i class="fas fa-save me-2"></i>SIMPAN PERUBAHAN');
        }, 3000);
    });

    // ========== 6. Form Submit Handler ==========
    $('#batchUpdateForm').on('submit', function(e) {
        const submitter = e.originalEvent && e.originalEvent.submitter;
        const isFinalVerify = submitter && submitter.name === 'final_verify';

        if (isFinalVerify && !confirm('Verifikasi akhir PM ini sekarang?\n\nPerubahan item akan disimpan dan status PM akan menjadi COMPLETED.')) {
            e.preventDefault();
            return;
        }

        const saveBtn = $('#saveButton');
        saveBtn.prop('disabled', true);
        saveBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>MENYIMPAN...');

        if (isFinalVerify) {
            $('#btnFinalVerify').prop('disabled', true);
            $('#btnFinalVerify').html('<i class="fas fa-spinner fa-spin me-2"></i>MEMVERIFIKASI...');
        }
    });

    // ========== 7. Initial Check ==========
    checkFinalVerifyStatus();

    // ========== 8. Keyboard Shortcuts (Optional) ==========
    $(document).on('keydown', function(e) {
        // Ctrl + S to save
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
            e.preventDefault();
            if (!$('#saveButton').prop('disabled')) {
                $('#batchUpdateForm').submit();
            }
        }
    });

    // ========== 9. Tooltip Initialization (if needed) ==========
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// ========== 10. Validation Logic (multi-pilih + Other) ==========
function validateBeforeComplete() {
    const conditions = document.querySelectorAll('.condition-select');
    let allFilled = true;
    let emptyItems = [];
    let otherEmptyItems = [];

    conditions.forEach((select, index) => {
        let value = $(select).val();
        if (value === null || value === undefined) value = [];
        if (!Array.isArray(value)) value = [value];
        value = value.filter(v => v !== '' && v !== null);

        const itemId = $(select).data('item-id') || $(select).attr('id').split('-')[1];
        const manualFilled = $('#other-list-' + itemId + ' .other-manual-input')
            .map(function() { return $(this).val().trim(); }).get()
            .filter(v => v !== '');

        const hasPredefined = value.filter(v => v !== 'Other').length > 0;
        const hasOtherFlag = value.includes('Other');

        if (hasOtherFlag && manualFilled.length === 0 && !hasPredefined) {
            // Hanya pilih Other tapi manual kosong
            allFilled = false;
            otherEmptyItems.push(index + 1);
        } else if (hasOtherFlag && manualFilled.length === 0 && hasPredefined) {
            // Punya pilihan baku + flag Other kosong -> tetap lolos? tidak, ingatkan isi manual atau hapus Other
            allFilled = false;
            otherEmptyItems.push(index + 1);
        } else if (!hasPredefined && manualFilled.length === 0) {
            allFilled = false;
            emptyItems.push(index + 1);
        }
    });

    if (!allFilled) {
        if (otherEmptyItems.length > 0) {
            alert(`⚠️ PERHATIAN!\n\nPilihan "Other" butuh input manual.\n\nItem ke #${otherEmptyItems.join(', ')} belum ada teks manualnya. Harap ketik minimal 1 hasil manual.`);
            return false;
        }
        const itemList = emptyItems.slice(0, 5).join(', ');
        const moreItems = emptyItems.length > 5 ? ` dan ${emptyItems.length - 5} item lainnya` : '';

        alert(`⚠️ PERHATIAN!\n\nHarap isi HASIL PENGECEKAN untuk semua item sebelum menyelesaikan.\n\nItem yang belum diisi: #${itemList}${moreItems}`);
        return false;
    }

    return confirm('✅ Kirim checklist ini ke Admin untuk diverifikasi?\n\nPastikan semua data sudah benar.');
}

// ========== 11. Prevent Accidental Page Leave ==========
let formChanged = false;

$(document).on('change input', 'textarea, .condition-select, .verif-checkbox, input[type="file"], .other-manual-input', function() {
    formChanged = true;
});

$('#batchUpdateForm').on('submit', function() {
    formChanged = false;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
        return e.returnValue;
    }
});
</script>
@endpush
