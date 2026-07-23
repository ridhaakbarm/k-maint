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
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('export.pm', ['start_date' => $dateFrom, 'end_date' => $dateTo]) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Export Detail PM
            </a>
            <!-- Removed Laporan Efektivitas link since technicianId is removed from filters -->
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-filter me-2 text-primary"></i>Filter Monitoring
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('monitoring.pm') }}">
                                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Periode</label>
                        <select name="period" class="form-select" onchange="toggleFilterFields()">
                            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulan Spesifik</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>
                    
                    <!-- Pilihan Bulan -->
                    <div class="col-md-3" id="monthFilterContainer" style="{{ $period === 'monthly' ? '' : 'display:none;' }}">
                        <label class="form-label fw-bold small">Pilih Bulan</label>
                        <input type="month" name="filter_month" class="form-control" value="{{ $filterMonth ?? now()->format('Y-m') }}">
                    </div>

                    <!-- Custom Range -->
                    <div class="col-md-4" id="customDateFilter" style="{{ $period === 'custom' ? '' : 'display:none;' }}">
                        <label class="form-label fw-bold small">Range Tanggal</label>
                        <div class="input-group">
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
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
            ['label' => 'Total Item Terjadwal', 'value' => $statusSummary['total_target'], 'icon' => 'fa-clipboard-list', 'class' => 'primary text-white'],
            ['label' => 'Item Dikerjakan', 'value' => $statusSummary['done'], 'icon' => 'fa-check-circle', 'class' => 'success text-white'],
            ['label' => 'Belum Dikerjakan', 'value' => $statusSummary['not_done'], 'icon' => 'fa-hourglass-half', 'class' => 'warning text-dark'],
            ['label' => 'Item Temuan (Not OK)', 'value' => $statusSummary['not_ok'], 'icon' => 'fa-exclamation-triangle', 'class' => 'danger text-white'],
            ['label' => 'Total Mesin (Asset PM)', 'value' => $statusSummary['total_machines'], 'icon' => 'fa-cogs', 'class' => 'dark text-white'],
            ['label' => 'Progress Keseluruhan', 'value' => $statusSummary['progress'] . '%', 'icon' => 'fa-percentage', 'class' => 'info text-dark'],
        ] as $card)
        <div class="col-md-2 col-sm-6">
            <div class="card border-0 shadow-sm bg-{{ $card['class'] }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small fw-bold opacity-75">{{ $card['label'] }}</div>
                            <h3 class="fw-bold mb-0">{{ $card['value'] }}</h3>
                        </div>
                        <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fas fa-tasks me-2 text-info"></i>Rincian Item PM Keseluruhan Sistem</span>
            <small class="text-muted">Target item terjadwal vs item dikerjakan (Live)</small>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach([
                    ['title' => 'Bulan Ini', 'key' => 'monthly', 'class' => 'primary', 'note' => 'Semua jadwal aktif bulan ini'],
                    ['title' => 'Minggu Berjalan', 'key' => 'weekly', 'class' => 'success', 'note' => 'Daily 7 hari + weekly minggu ini'],
                    ['title' => 'Hari Ini', 'key' => 'daily', 'class' => 'dark', 'note' => 'Jadwal daily aktif hari ini'],
                ] as $summaryCard)
                    @php
                        $summary = $pmItemPeriodSummary[$summaryCard['key']] ?? ['target' => 0, 'done' => 0, 'progress' => 0];
                    @endphp
                    <div class="col-lg-4">
                        <div class="pm-item-summary-box border-{{ $summaryCard['class'] }} p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase">{{ $summaryCard['title'] }}</div>
                                    <div class="small text-muted">{{ $summaryCard['note'] }}</div>
                                </div>
                                <span class="badge bg-{{ $summaryCard['class'] }}">{{ $summary['progress'] }}%</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Terjadwal</span>
                                <strong>{{ $summary['target'] }} item</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Dikerjakan</span>
                                <strong class="text-{{ $summaryCard['class'] }}">{{ $summary['done'] }} item</strong>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-{{ $summaryCard['class'] }}" style="width: {{ min($summary['progress'], 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Per-Machine Progress Section -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-industry me-2 text-success"></i>Progress PM per Mesin (Asset)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-3">Nama Mesin (Asset)</th>
                            <th class="text-center">Total Item Terjadwal</th>
                            <th class="text-center">Item Dikerjakan</th>
                            <th class="text-center">Belum Dikerjakan</th>
                            <th style="width: 250px;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assetProgress as $asset)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $asset['asset_name'] }}</td>
                            <td class="text-center">{{ $asset['target'] }}</td>
                            <td class="text-center text-success fw-bold">{{ $asset['done'] }}</td>
                            <td class="text-center text-danger fw-bold">{{ $asset['not_done'] }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 12px;">
                                        <div class="progress-bar bg-{{ $asset['progress'] == 100 ? 'success' : ($asset['progress'] >= 50 ? 'primary' : 'warning') }}" style="width: {{ min($asset['progress'], 100) }}%"></div>
                                    </div>
                                    <span class="small fw-bold">{{ $asset['progress'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada data mesin dengan jadwal PM di periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Technician Performance -->
        <div class="col-12">
            <div class="card shadow-sm h-100 border-dark">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users-cog me-2 text-dark"></i>Performa Teknisi PM</span>
                    <span class="badge bg-dark">{{ $technicianPerformance->count() }} Teknisi Aktif</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Teknisi</th>
                                    <th class="text-center">Total Item Dikerjakan</th>
                                    <th class="text-center">Waktu Pengerjaan (Jam)</th><th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($technicianPerformance as $perf)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-placeholder bg-light rounded-circle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                {{ substr($perf['technician']->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $perf['technician']->name }}</div>
                                                <div class="small text-muted">{{ $perf['technician']->department }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center h5 mb-0 text-primary fw-bold">{{ $perf['done_items'] }}</td>
                                    <td class="text-center text-dark fw-bold">{{ $perf['pm_hours'] }} Jam</td>
                                    <td class="text-end">
                                        <a href="{{ route('export.technician-pm-items', ['technician_id' => $perf['technician']->id, 'start_date' => $dateFrom, 'end_date' => $dateTo]) }}" class="btn btn-sm btn-outline-success" title="Download Excel Data Item yang Dikerjakan">
                                            <i class="fas fa-file-excel"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada performa teknisi PM untuk periode ini.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab Tambahan -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-danger">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-triangle me-2 text-danger"></i>PM Overdue (10 Terlama)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Jadwal PM</th>
                                <th>Due Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overdueChecks as $check)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $check->pmSchedule->name ?? '-' }}<br><small class="text-muted">{{ $check->pmSchedule->asset->name ?? '-' }}</small></td>
                                <td class="text-danger fw-bold">{{ \Carbon\Carbon::parse($check->due_date)->format('d M Y') }}</td>
                                <td class="text-center"><span class="badge bg-danger">{{ $check->status }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Tidak ada jadwal PM overdue.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-warning">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-check me-2 text-warning"></i>Butuh Verifikasi Admin</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Jadwal PM</th>
                                <th>Update Terakhir</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($needVerificationChecks as $check)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $check->pmSchedule->name ?? '-' }}<br><small class="text-muted">{{ $check->pmSchedule->asset->name ?? '-' }}</small></td>
                                <td>{{ $check->updated_at->diffForHumans() }}</td>
                                <td class="text-center">
                                    <a href="{{ route('pm.execution.show', $check->id) }}" class="btn btn-sm btn-primary">Review</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Tidak ada PM yang menunggu verifikasi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFilterFields() {
    var period = document.querySelector('select[name="period"]').value;
    document.getElementById('monthFilterContainer').style.display = (period === 'monthly') ? '' : 'none';
    document.getElementById('customDateFilter').style.display = (period === 'custom') ? '' : 'none';
}
</script>
@endsection