@extends('layouts.app')

@section('page_title', 'Preview Template PM - Read Only')
@section('breadcrumb', 'Preview Template PM')

@push('css')
<style>
    .preview-card {
        border-radius: 12px;
        overflow: hidden;
    }

    .preview-header {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        padding: 16px 20px;
    }

    .table {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #f8f9fa !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        vertical-align: middle;
        padding: 12px 8px;
        border-bottom: 2px solid #dee2e6;
        text-align: center;
    }

    .table td {
        vertical-align: middle;
        padding: 12px 8px;
    }

    .item-name {
        font-weight: 700;
        color: #0d6efd;
        font-size: 1rem;
        margin-bottom: 4px;
    }

    .part-detail {
        background: #e9ecef;
        padding: 6px 10px;
        border-radius: 4px;
        color: #495057;
        font-size: 0.85rem;
        display: inline-block;
    }

    .instruction-box {
        background: #fff3cd;
        border-left: 3px solid #ffc107;
        padding: 10px;
        border-radius: 4px;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .standard-badge {
        background: #cfe2ff;
        color: #084298;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        display: inline-block;
    }

    .row-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: #6c757d;
        color: white;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .info-card {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 12px;
        border: 1px solid #dee2e6;
    }

    .info-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #212529;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Header Info Cards --}}
            <div class="row mb-3">
                <div class="col-md-3 col-6">
                    <div class="info-card">
                        <div class="info-label">
                            <i class="fas fa-cog me-1"></i>Mesin / Aset
                        </div>
                        <div class="info-value">{{ $schedule->asset->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-card">
                        <div class="info-label">
                            <i class="fas {{ $schedule->schedule_type === 'daily' ? 'fa-calendar-day' : 'fa-calendar-week' }} me-1"></i>{{ $schedule->schedule_type === 'daily' ? 'Tanggal' : 'Week' }}
                        </div>
                        <div class="info-value text-primary">
                            {{ $schedule->schedule_type === 'daily' ? \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') : $weekNumber }}
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-card">
                        <div class="info-label">
                            <i class="fas fa-list-check me-1"></i>Total Item
                        </div>
                        <div class="info-value">{{ $activeTemplates->count() }} Checklist</div>
                    </div>
                </div>
            </div>

            {{-- Read-Only Notice --}}
            <div class="alert alert-info d-flex align-items-center shadow-sm mb-3">
                <i class="fas fa-eye fa-2x me-3"></i>
                <div>
                    <strong>MODE PREVIEW (READ-ONLY)</strong>
                    <p class="mb-0 small">
                        Ini adalah daftar checklist untuk
                        <strong>{{ $schedule->schedule_type === 'daily' ? 'tanggal '.\Carbon\Carbon::parse($selectedDate)->format('d/m/Y') : 'Week '.$weekNumber }}</strong>.
                        @if(auth()->user()->isMTC() && $schedule->schedule_type !== 'daily' && $weekNumber != now()->weekOfYear)
                        Sebagai teknisi MTC, Anda hanya dapat melihat. Untuk mulai mengisi,
                        silakan kembali ke <strong>Week {{ now()->weekOfYear }}</strong>.
                        @elseif(auth()->user()->isMTC() && $schedule->schedule_type === 'daily' && $selectedDate != now()->toDateString())
                        Sebagai teknisi MTC, Anda hanya dapat melihat. Untuk mulai mengisi,
                        silakan kembali ke <strong>tanggal hari ini</strong>.
                        @endif
                    </p>
                </div>
            </div>

            @if($existingPmCheck)
                <div class="alert alert-success d-flex justify-content-between align-items-center shadow-sm mb-3 flex-wrap gap-2">
                    <div>
                        <strong>Checklist periode ini sudah pernah dibuat.</strong>
                        <div class="small mb-0">Anda bisa langsung buka checklist yang sudah ada tanpa membuat record baru.</div>
                    </div>
                    <a href="{{ route('pm.execution.show', $existingPmCheck->id) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-eye me-2"></i>Lihat Checklist
                    </a>
                </div>
            @endif

            {{-- Checklist Table --}}
            <div class="card shadow-sm border-0 preview-card">
                <div class="preview-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-clipboard-list me-2"></i>
                            DAFTAR PENGECEKAN - {{ $schedule->schedule_type === 'daily' ? 'DAILY '.\Carbon\Carbon::parse($selectedDate)->format('d/m/Y') : 'WEEK '.$weekNumber }}
                        </h5>
                        <span class="badge bg-light text-dark">
                            {{ $activeTemplates->count() }} Items
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($activeTemplates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th style="width: 200px;">Item & Bagian</th>
                                        <th style="width: 250px;">Instruksi Pengecekan</th>
                                        <th style="width: 150px;">Standard</th>
                                        <th>{{ $schedule->schedule_type === 'daily' ? 'Periode' : 'Week Aktif' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeTemplates as $index => $template)
                                        <tr>
                                            {{-- No --}}
                                            <td class="text-center">
                                                <span class="row-number">{{ $index + 1 }}</span>
                                            </td>

                                            {{-- Item & Part --}}
                                            <td>
                                                <div class="item-name">
                                                    {{ $template->item_name }}
                                                </div>
                                                <div class="part-detail">
                                                    <i class="fas fa-puzzle-piece me-1"></i>
                                                    {{ $template->checked_part }}
                                                </div>
                                            </td>

                                            {{-- Instructions --}}
                                            <td>
                                                <div class="instruction-box">
                                                    {{ $template->instructions }}
                                                </div>
                                            </td>

                                            {{-- Standard --}}
                                            <td>
                                                <span class="standard-badge">
                                                    <i class="fas fa-ruler me-1"></i>
                                                    {{ $template->check_standard }}
                                                </span>
                                            </td>

                                            {{-- Active Weeks --}}
                                            <td>
                                                <small class="text-muted">
                                                    @if($schedule->schedule_type === 'daily')
                                                        Setiap hari
                                                    @elseif(is_array($template->active_weeks))
                                                        {{ implode(', ', $template->active_weeks) }}
                                                    @else
                                                        {{ $template->active_weeks }}
                                                    @endif
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <h5>Tidak Ada Checklist untuk Week {{ $weekNumber }}</h5>
                            <p class="text-muted mb-0">
                                Template checklist belum dikonfigurasi untuk periode ini.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer with Back Button --}}
                <div class="card-footer bg-white border-0 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Tampilan preview hanya untuk melihat daftar pengecekan
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            @if($canStartChecklist)
                                <a href="{{ route('pm-checks.create', $schedule->id) }}?{{ $schedule->schedule_type === 'daily' ? 'date='.$selectedDate : 'week='.$weekNumber }}" class="btn btn-primary">
                                    <i class="fas fa-play-circle me-2"></i>
                                    MULAI CHECKLIST
                                </a>
                            @endif
                            <a href="{{ route('pm.execution.index', ['scheduleType' => $schedule->schedule_type, 'week' => $weekNumber, 'date' => $selectedDate]) }}"
                               class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                KEMBALI
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
