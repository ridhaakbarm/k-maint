@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fas fa-chart-pie me-2 text-primary"></i>Monitoring PM
            </h4>
            <small class="text-muted">Ringkasan kondisi Preventive Maintenance, progres checklist, dan performa teknisi.</small>
        </div>
        <a href="{{ route('export.pm', ['start_date' => $dateFrom, 'end_date' => $dateTo]) }}" class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Export Detail PM
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-filter me-2 text-primary"></i>Filter Monitoring
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('monitoring.pm') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Periode</label>
                        <select name="period" class="form-select" onchange="this.form.submit()">
                            <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Harian</option>
                            <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Range Tanggal</label>
                        <div class="input-group">
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Tipe Jadwal</label>
                        <select name="schedule_type" class="form-select">
                            <option value="all" {{ $scheduleType === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $key => $label)
                                <option value="{{ $key }}" {{ $scheduleType === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Teknisi</label>
                        <select name="technician_id" class="form-select">
                            <option value="all" {{ $technicianId === 'all' ? 'selected' : '' }}>Semua Teknisi</option>
                            @foreach($technicians as $technician)
                                <option value="{{ $technician->id }}" {{ (string) $technicianId === (string) $technician->id ? 'selected' : '' }}>
                                    {{ $technician->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Total PM', 'value' => $statusSummary['total'], 'icon' => 'fa-clipboard-list', 'class' => 'primary text-white'],
            ['label' => 'Belum Usai', 'value' => $statusSummary['belum_usai'], 'icon' => 'fa-hourglass-half', 'class' => 'warning text-dark'],
            ['label' => 'Selesai', 'value' => $statusSummary['selesai'], 'icon' => 'fa-check-circle', 'class' => 'success text-white'],
            ['label' => 'Closed', 'value' => $statusSummary['closed'], 'icon' => 'fa-lock', 'class' => 'dark text-white'],
            ['label' => 'Overdue', 'value' => $statusSummary['overdue'], 'icon' => 'fa-exclamation-triangle', 'class' => 'danger text-white'],
            ['label' => 'Butuh Verifikasi', 'value' => $statusSummary['waiting_verification'], 'icon' => 'fa-user-check', 'class' => 'info text-dark'],
        ] as $card)
        <div class="col-md-2 col-sm-6">
            <div class="card border-0 shadow-sm bg-{{ $card['class'] }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small fw-bold opacity-75">{{ $card['label'] }}</div>
                            <h2 class="fw-bold mb-0">{{ $card['value'] }}</h2>
                        </div>
                        <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calendar-day me-2 text-primary"></i>Planning PM Hari Ini & Bulan Berjalan</span>
            <small class="text-muted">{{ now()->format('d/m/Y') }}</small>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2 col-sm-6">
                    <div class="monitor-mini-box border-primary">
                        <div class="text-muted small fw-bold">PM Aktif Bulan Ini</div>
                        <div class="h3 fw-bold mb-0 text-primary">{{ $activeMonthSchedules->count() }}</div>
                        <small class="text-muted">jadwal aktif</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="monitor-mini-box border-dark">
                        <div class="text-muted small fw-bold">Terjadwal Hari Ini</div>
                        <div class="h3 fw-bold mb-0 text-dark">{{ $todaySummary['scheduled'] }}</div>
                        <small class="text-muted">PM due hari ini</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="monitor-mini-box border-danger">
                        <div class="text-muted small fw-bold">Belum Dilakukan</div>
                        <div class="h3 fw-bold mb-0 text-danger">{{ $todaySummary['not_started'] }}</div>
                        <small class="text-muted">belum ada checklist</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="monitor-mini-box border-warning">
                        <div class="text-muted small fw-bold">Sedang Proses</div>
                        <div class="h3 fw-bold mb-0 text-warning">{{ $todaySummary['in_progress'] }}</div>
                        <small class="text-muted">pending/progress/verif</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="monitor-mini-box border-success">
                        <div class="text-muted small fw-bold">Sudah Dilakukan</div>
                        <div class="h3 fw-bold mb-0 text-success">{{ $todaySummary['done'] }}</div>
                        <small class="text-muted">selesai/closed</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="monitor-mini-box border-info">
                        <div class="text-muted small fw-bold">Tindakan Selanjutnya</div>
                        <div class="h3 fw-bold mb-0 text-info">{{ $followUpSummary['total'] }}</div>
                        <small class="text-muted">{{ $followUpSummary['open'] }} open, {{ $followUpSummary['ok'] }} OK</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-tasks me-2 text-primary"></i>Progress Item Checklist
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Item selesai</span>
                        <strong>{{ $statusSummary['done_items'] }}/{{ $statusSummary['total_items'] }}</strong>
                    </div>
                    <div class="progress mb-3" style="height: 14px;">
                        <div class="progress-bar bg-success" style="width: {{ $statusSummary['progress'] }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-success">{{ $statusSummary['progress'] }}% complete</span>
                        <span class="badge bg-danger">{{ $statusSummary['not_ok_items'] }} temuan Not OK</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-layer-group me-2 text-primary"></i>Breakdown Tipe Jadwal
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Tipe</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Done/Closed</th>
                                    <th class="text-center">Belum Usai</th>
                                    <th style="width: 220px;">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($scheduleTypeSummary as $row)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ strtoupper($row['type']) }}</td>
                                    <td class="text-center">{{ $row['total'] }}</td>
                                    <td class="text-center text-success fw-bold">{{ $row['done'] }}</td>
                                    <td class="text-center text-danger fw-bold">{{ $row['open'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 10px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $row['rate'] }}%"></div>
                                            </div>
                                            <span class="small fw-bold">{{ $row['rate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data PM di periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-cog me-2 text-primary"></i>Performance Teknisi PM</span>
            <small class="text-muted">Diurutkan dari progress item tertinggi</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Teknisi</th>
                            <th class="text-center">Assigned</th>
                            <th class="text-center">Selesai</th>
                            <th class="text-center">Closed</th>
                            <th class="text-center">Belum Usai</th>
                            <th class="text-center">Item</th>
                            <th class="text-center">Not OK</th>
                            <th class="text-center">Jam PM</th>
                            <th style="width: 220px;">Item Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($technicianPerformance as $row)
                        <tr>
                            <td class="ps-3">
                                <strong>{{ $row['technician']->name }}</strong>
                                <small class="text-muted d-block">{{ strtoupper($row['technician']->role) }}</small>
                            </td>
                            <td class="text-center fw-bold">{{ $row['assigned'] }}</td>
                            <td class="text-center text-success fw-bold">{{ $row['completed'] }}</td>
                            <td class="text-center text-dark fw-bold">{{ $row['closed'] }}</td>
                            <td class="text-center text-danger fw-bold">{{ $row['not_finished'] }}</td>
                            <td class="text-center">{{ $row['done_items'] }}/{{ $row['total_items'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $row['not_ok_items'] > 0 ? 'danger' : 'secondary' }}">{{ $row['not_ok_items'] }}</span>
                            </td>
                            <td class="text-center">{{ $row['pm_hours'] }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: {{ $row['item_rate'] }}%"></div>
                                    </div>
                                    <span class="small fw-bold">{{ $row['item_rate'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Belum ada pekerjaan PM teknisi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            @include('monitoring.partials.pm-list', [
                'title' => 'PM Overdue',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'danger',
                'items' => $overdueChecks,
                'empty' => 'Tidak ada PM overdue.'
            ])
        </div>
        <div class="col-lg-4">
            @include('monitoring.partials.pm-list', [
                'title' => 'Menunggu Verifikasi',
                'icon' => 'fa-user-check',
                'color' => 'info',
                'items' => $needVerificationChecks,
                'empty' => 'Tidak ada PM menunggu verifikasi.'
            ])
        </div>
        <div class="col-lg-4">
            @include('monitoring.partials.pm-list', [
                'title' => 'Terakhir Selesai',
                'icon' => 'fa-check-double',
                'color' => 'success',
                'items' => $recentFinishedChecks,
                'empty' => 'Belum ada PM selesai di periode ini.'
            ])
        </div>
    </div>
</div>
<style>
    .monitor-mini-box {
        border-left: 4px solid;
        background: #fff;
        border-radius: 6px;
        padding: 14px 16px;
        height: 100%;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
</style>
@endsection
