@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- HEADER & FILTER MINGGU (Tetap Sama) --}}
        <div class="bg-dark text-white border-0 shadow-sm d-flex justify-content-between align-items-center flex-wrap p-3 rounded mb-3">
            <div class="mb-2 mb-md-0">
                <h5 class="mb-0 fw-bold text-white">
                    <i class="fas fa-calendar-alt me-2 text-warning"></i> 
                    Monitoring PM: {{ ucfirst($scheduleType) }}
                </h5>
                <small class="text-white-50">
                    Jadwal Aktif:
                    <strong>
                        {{ $scheduleType === 'daily' ? \Carbon\Carbon::parse($currentDate)->format('d/m/Y') : 'Week '.$currentWeek }}
                    </strong>
                </small>
            </div>
            
            <form action="{{ route('pm.execution.index', $scheduleType) }}" method="GET" class="d-flex align-items-center bg-white p-1 rounded shadow-sm">
                <input type="hidden" name="status" value="{{ request('status') }}"> {{-- Menjaga filter status saat ganti week --}}
                @if($scheduleType === 'daily')
                    <label class="mx-2 mb-0 small fw-bold text-dark">TANGGAL:</label>
                    <input type="date" name="date" value="{{ $currentDate }}" class="form-control form-control-sm border-0 fw-bold text-primary" onchange="this.form.submit()" style="width: 150px;">
                    @if($currentDate != now()->toDateString())
                        <a href="{{ route('pm.execution.index', 'daily') }}" class="btn btn-xs btn-danger ms-2 me-1">
                            <i class="fas fa-sync-alt"></i> Hari Ini
                        </a>
                    @endif
                @else
                    <label class="mx-2 mb-0 small fw-bold text-dark">LIHAT WEEK:</label>
                    <select name="week" class="form-select form-select-sm border-0 fw-bold text-primary" onchange="this.form.submit()" style="width: 80px;">
                        @for($i = 1; $i <= 52; $i++)
                            <option value="{{ $i }}" {{ $currentWeek == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    @if($currentWeek != now()->weekOfYear)
                        <a href="{{ route('pm.execution.index', $scheduleType) }}" class="btn btn-xs btn-danger ms-2 me-1">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    @endif
                @endif
            </form>
        </div>

        <div class="btn-group mb-3" role="group">
            @foreach(['daily' => 'PM Daily', 'weekly' => 'PM Weekly', 'yearly' => 'PM Yearly'] as $type => $label)
                <a href="{{ route('pm.execution.index', $type) }}"
                   class="btn btn-sm {{ $scheduleType === $type ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- DAFTAR TUGAS AKTIF (Tetap Sama) --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title fw-bold text-dark">Daftar Tugas (Week {{ $currentWeek }})</h3>

                {{-- Search Box untuk Pencarian --}}
                <form action="{{ route('pm.execution.index', $scheduleType) }}" method="GET" class="d-flex gap-2 mt-2 mt-md-0">
                    @if($scheduleType === 'daily')
                        <input type="hidden" name="date" value="{{ $currentDate }}">
                    @else
                        <input type="hidden" name="week" value="{{ $currentWeek }}">
                    @endif
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari mesin / teknisi..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    @if(request('search'))
                        <a href="{{ route('pm.execution.index', ['scheduleType' => $scheduleType, 'week' => $currentWeek, 'date' => $currentDate, 'status' => request('status')]) }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    @endif
                </form>
            </div>
            <div class="card-body">
                <div class="row">
                    @forelse($schedules as $item)
                        @php
                            $schedule = ($viewMode === 'technician_task') ? $item->pmSchedule : $item;
                            $assignment = ($viewMode === 'technician_task') ? $item : null;
                            $activeItemCount = $schedule->checklistTemplates
                                ->where('is_active', true)
                                ->filter(function($template) use ($currentWeek, $schedule) {
                                    if ($schedule->schedule_type === 'daily') {
                                        return true;
                                    }

                                    return is_array($template->active_weeks)
                                        && (
                                            in_array($currentWeek, $template->active_weeks)
                                            || in_array((string) $currentWeek, $template->active_weeks)
                                        );
                                })
                                ->count();
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-start border-4 border-{{ $viewMode === 'technician_task' ? 'warning' : 'success' }} shadow-sm hover-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="fw-bold mb-0 text-primary">{{ $schedule->asset->name ?? 'Aset Tidak Ditemukan' }}</h5>
                                        </div>
                                        <span class="badge bg-light text-dark border">
                                            {{ $activeItemCount }} Items
                                        </span>
                                    </div>
                                    @if($schedule->pic_name)
                                    <div class="mb-2">
                                        <small class="text-muted"><i class="fas fa-user me-1"></i> PIC:</small>
                                        <strong class="text-info">{{ $schedule->pic_name }}</strong>
                                    </div>
                                    @endif
                                    <div class="bg-light p-2 rounded small text-center">
                                        Status: <strong>{{ $assignment ? strtoupper(str_replace('_', ' ', $assignment->status)) : 'READY TO CHECK' }}</strong>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0 pb-3">
                                    @if($viewMode === 'technician_task')
                                        <a href="{{ route('pm.execution.show', $assignment->id) }}" class="btn btn-warning btn-sm w-100 fw-bold">
                                            <i class="fas fa-edit me-1"></i> DETAIL / ISI CHECKLIST
                                        </a>
                                    @else
                                        @php
                                            $actualCurrentWeek = (int) now()->weekOfYear;
                                            $isCurrentWeek = ($currentWeek === $actualCurrentWeek);
                                            $isCurrentDate = ($currentDate === now()->toDateString());
                                            $userIsMTC = auth()->user() && auth()->user()->isMTC();
                                            $existingPmCheck = $existingChecksBySchedule[$schedule->id] ?? null;
                                            $doneItems = $existingPmCheck->done_items_count ?? 0;
                                            $totalItems = $existingPmCheck->total_items_count ?? $activeItemCount;
                                        @endphp

                                        <div class="mb-2 text-center">
                                            <span class="badge pm-item-progress px-3">
                                                <i class="fas fa-tasks me-1"></i>{{ $doneItems }}/{{ $totalItems }} item dikerjakan
                                            </span>
                                        </div>

                                        @if($existingPmCheck)
                                            {{-- JIKA SUDAH ADA PM CHECK: Tampilkan tombol LIHAT/DETAIL --}}
                                            <a href="{{ route('pm.execution.show', $existingPmCheck->id) }}" class="btn btn-info btn-sm w-100 fw-bold">
                                                <i class="fas fa-eye me-1"></i> LIHAT CHECKLIST WEEK {{ $currentWeek }}
                                            </a>
                                        @else
                                            <a href="{{ route('pm-checks.preview', $schedule->id) }}?{{ $scheduleType === 'daily' ? 'date='.$currentDate : 'week='.$currentWeek }}"
                                               class="btn btn-outline-secondary btn-sm w-100 fw-bold mb-2">
                                                <i class="fas fa-search me-1"></i> LIHAT DAFTAR TUGAS
                                            </a>

                                            @if(!$userIsMTC || ($scheduleType === 'daily' ? $isCurrentDate : $isCurrentWeek))
                                                <a href="{{ route('pm-checks.create', $schedule->id) }}?{{ $scheduleType === 'daily' ? 'date='.$currentDate : 'week='.$currentWeek }}" class="btn btn-primary btn-sm w-100 fw-bold">
                                                    <i class="fas fa-play-circle me-1"></i> MULAI CEK {{ $scheduleType === 'daily' ? \Carbon\Carbon::parse($currentDate)->format('d/m/Y') : 'WEEK '.$currentWeek }}
                                                </a>
                                            @else
                                                <small class="text-muted d-block text-center mt-1">
                                                    <i class="fas fa-info-circle"></i> Read-only (Mode Lihat)
                                                </small>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4 text-muted">Tidak ada jadwal PM aktif.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TABEL RIWAYAT PM DENGAN FILTER STATUS --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0 small fw-bold text-muted text-uppercase tracking-wider">Riwayat PM: {{ ucfirst($scheduleType) }}</h5>
                
                {{-- REVISI: FILTER STATUS --}}
                <div class="btn-group mt-2 mt-md-0" role="group">
                    <a href="{{ route('pm.execution.index', ['scheduleType' => $scheduleType, 'week' => $currentWeek, 'date' => $currentDate]) }}" 
                       class="btn btn-xs {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }}">SEMUA</a>
                    
                    <a href="{{ route('pm.execution.index', ['scheduleType' => $scheduleType, 'week' => $currentWeek, 'date' => $currentDate, 'status' => 'in_progress']) }}" 
                       class="btn btn-xs {{ request('status') == 'in_progress' ? 'btn-warning' : 'btn-outline-warning' }}">PROGRES</a>
                    
                    <a href="{{ route('pm.execution.index', ['scheduleType' => $scheduleType, 'week' => $currentWeek, 'date' => $currentDate, 'status' => 'waiting_verification']) }}" 
                       class="btn btn-xs {{ request('status') == 'waiting_verification' ? 'btn-danger' : 'btn-outline-danger' }}">BUTUH VERIF</a>
                    
                    <a href="{{ route('pm.execution.index', ['scheduleType' => $scheduleType, 'week' => $currentWeek, 'date' => $currentDate, 'status' => 'completed']) }}" 
                       class="btn btn-xs {{ request('status') == 'completed' ? 'btn-success' : 'btn-outline-success' }}">SELESAI</a>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small align-middle">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="ps-3">ASET</th>
                                <th>{{ $scheduleType === 'daily' ? 'TANGGAL' : 'TANGGAL CEK' }}</th>
                                <th>TEKNISI</th>
                                <th class="text-center">ITEM PM</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allChecks as $check)
                            <tr>
                                <td class="ps-3">
                                    <strong class="text-primary">{{ $check->pmSchedule->asset->name ?? '-' }}</strong>
                                </td>
                                <td>
                                    {{ $check->check_date ? $check->check_date->format('d/m/Y') : '-' }}
                                    <br><small class="text-muted">{{ $scheduleType === 'daily' ? 'Daily' : 'Week '.$check->week_number }}</small>
                                </td>
                                <td>{{ $check->technician_name }}</td>
                                <td class="text-center">
                                    <span class="badge pm-item-progress px-3">
                                        {{ $check->done_items_count ?? 0 }}/{{ $check->total_items_count ?? 0 }} item
                                    </span>
                                </td>
                                <td class="text-center">
                                    {{-- REVISI BADGE COLOR --}}
                                    @php
                                        $badgeColor = [
                                            'in_progress' => 'warning',
                                            'waiting_verification' => 'danger',
                                            'completed' => 'success',
                                            'verified' => 'info'
                                        ][$check->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge rounded-pill bg-{{ $badgeColor }} px-3">
                                        {{ strtoupper(str_replace('_', ' ', $check->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        @if($check->status == 'in_progress')
                                            <form action="{{ route('pm.execution.startWork', $check->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-warning fw-bold" title="Lanjutkan Rekam">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('pm.execution.show', $check->id) }}" class="btn btn-xs btn-outline-primary px-3">DETAIL</a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">Data tidak ditemukan untuk filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-card { transition: all 0.3s; }
    .hover-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important; }
    .btn-xs { padding: 0.2rem 0.6rem; font-size: 0.7rem; font-weight: bold; }
    .tracking-wider { letter-spacing: 0.05em; }
    .pm-item-progress {
        background: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #b6d4fe;
        font-weight: 700;
    }
</style>
@endsection
