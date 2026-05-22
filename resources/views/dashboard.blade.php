@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(Auth::user()->isAdmin())
    {{-- Header & Global Filter --}}
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
        <h3 class="fw-bold mb-0">Dashboard Monitoring</h3>
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" style="width: 100px;">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
        </form>
    </div>

    {{-- BARIS STATISTIK ATAS --}}
    <div class="row g-3 mb-4">
        {{-- Kelompok Tiket Breakdown --}}
        <div class="col-xl-6">
            <div class="p-3 bg-white shadow-sm rounded-3 border">
                <h6 class="fw-bold mb-3 text-muted small text-uppercase"><i class="fas fa-ticket-alt me-2 text-primary"></i>Maintenance Tickets</h6>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="p-2 bg-warning rounded-3 text-center">
                            <div class="small fw-bold">OPEN</div>
                            <h3 class="fw-bold mb-0">{{ $ticketStats['open'] }}</h3>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-info text-white rounded-3 text-center">
                            <div class="small fw-bold">PROGRESS</div>
                            <h3 class="fw-bold mb-0">{{ $ticketStats['onprogress'] }}</h3>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-success text-white rounded-3 text-center">
                            <div class="small fw-bold">CLOSED</div>
                            <h3 class="fw-bold mb-0">{{ $ticketStats['closed'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kelompok Preventive Maintenance --}}
        <div class="col-xl-6">
            <div class="p-3 bg-white shadow-sm rounded-3 border">
                <h6 class="fw-bold mb-3 text-muted small text-uppercase"><i class="fas fa-calendar-check me-2 text-success"></i>Preventive Maintenance (Week {{ $currentWeek }})</h6>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="p-2 bg-secondary text-white rounded-3 text-center">
                            <div class="small fw-bold">SCHEDULED</div>
                            <h3 class="fw-bold mb-0">{{ $pmStats['machines_total'] }} <small class="fs-6">Unit</small></h3>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-success text-white rounded-3 text-center position-relative">
                            <div class="small fw-bold">MESIN DONE</div>
                            <h3 class="fw-bold mb-0">{{ $pmStats['machines_done'] }}</h3>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-primary text-white rounded-3 text-center">
                            <div class="small fw-bold">ITEMS DONE</div>
                            <h3 class="fw-bold mb-0">{{ $pmStats['items_done'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Baris Kedua: Pie Chart Global & Live Monitoring --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold border-0 pt-3">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>% Distribusi Kerja Tim (Hari Ini)
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="workChart" style="max-height: 220px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-desktop me-2 text-danger pulse"></i> LIVE MONITORING</span>
                    <span class="badge bg-danger small">REAL-TIME</span>
                </div>
                <div class="card-body p-0 table-responsive" style="max-height: 300px;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Teknisi</th>
                                <th>Kategori</th>
                                <th>Pekerjaan / Mesin</th>
                                <th>Mulai</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeActivities as $act)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $act->user->name }}</td>
                                <td>
                                    @php $color = $act->category == 'PM' ? 'success' : ($act->category == 'Breakdown' ? 'info' : 'secondary'); @endphp
                                    <span class="badge bg-{{ $color }}">{{ $act->category }}</span>
                                </td>
                                <td>{{ $act->description }}</td>
                                <td>{{ $act->start_time->format('H:i') }}</td>
                                <td class="text-center"><span class="badge bg-success">WORKING</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted italic">Tidak ada aktivitas teknisi saat ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS KETIGA: WORKLOAD INDIVIDU (RASIO PEKERJAAN) --}}
    <h5 class="fw-bold mb-3 mt-4"><i class="fas fa-users-cog me-2 text-primary"></i>Produktivitas Teknisi Harian (MTC)</h5>
<div class="row">
    @foreach($technicianStats as $stat)
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom-0 pt-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-user-circle me-1 text-muted"></i> {{ $stat['user']->name }}
                </h6>
                @if($stat['attendance'])
                    <span class="badge rounded-pill bg-{{ $stat['workload_pct'] > 80 ? 'success' : ($stat['workload_pct'] > 50 ? 'primary' : 'warning text-dark') }}">
                        Workload: {{ $stat['workload_pct'] }}%
                    </span>
                @else
                    <span class="badge rounded-pill bg-light text-muted border">OFFLINE</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row align-items-center mb-3">
                    <div class="col-5 text-center">
    @if($stat['total_work_minutes'] > 0)
        {{-- Tampilkan Chart jika ada data pengerjaan --}}
        <canvas id="chart-{{ $stat['user']->id }}" style="max-height: 100px;"></canvas>
    @else
        {{-- Tampilkan Icon Placeholder jika belum ada kerjaan / offline --}}
        <div class="py-3">
            <i class="fas fa-user-clock fa-3x text-light"></i>
            <p class="x-small text-muted mt-2 mb-0">No Activity</p>
        </div>
    @endif
</div>
                    <div class="col-7 small border-start ps-3">
                        @if($stat['attendance'])
                            <p class="mb-1 text-muted">Clock In: <span class="text-success fw-bold">{{ $stat['attendance']->clock_in->format('H:i') }}</span></p>
                            <p class="mb-1 text-muted">Clock Out: <span class="text-dark fw-bold">{{ $stat['attendance']->clock_out ? $stat['attendance']->clock_out->format('H:i') : 'Active' }}</span></p>
                            <p class="mb-0 text-muted">Total Kerja: <span class="text-primary fw-bold">{{ $stat['total_work_minutes'] }} m</span></p>
                        @else
                            <div class="py-2 text-center text-muted italic">Belum Clock-In hari ini kawan.</div>
                        @endif
                    </div>
                </div>

                {{-- LIST KERJAAN HARI INI --}}
                <div class="bg-light rounded-2 p-2">
                    <h6 class="x-small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.7rem;">Aktivitas Hari Ini:</h6>
                    <div style="max-height: 120px; overflow-y: auto;">
                        <ul class="list-unstyled mb-0 small">
                            @forelse($stat['activities'] as $act)
                                <li class="mb-1 d-flex justify-content-between border-bottom pb-1">
                                    <span class="text-truncate" style="max-width: 150px;">
                                        <span class="badge bg-{{ $act->category == 'PM' ? 'success' : 'info' }} p-1" style="font-size: 0.6rem;">{{ $act->category }}</span>
                                        {{ $act->description }}
                                    </span>
                                    <span class="text-muted fw-bold">{{ $act->start_time->format('H:i') }}</span>
                                </li>
                            @empty
                                <li class="text-muted small italic">Belum ada catatan kerja.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

    {{-- BARIS KEEMPAT: LIST TIKET OPEN & JADWAL PM --}}
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-header bg-warning text-dark fw-bold border-0">
                    <i class="fas fa-exclamation-circle me-2"></i> TIKET BREAKDOWN (OPEN)
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($openTickets as $ticket)
                        <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-1 fw-bold text-primary">{{ $ticket->ticket_no }}</h6>
                                <small class="text-muted">{{ $ticket->request_date->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1 small fw-bold text-dark">{{ $ticket->subject }}</p>
                            <small class="text-muted"><i class="fas fa-cog me-1"></i> Mesin: {{ $ticket->asset->name ?? '-' }}</small>
                        </a>
                        @empty
                        <div class="text-center py-5 text-muted small">Tidak ada tiket open.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-header bg-success text-white fw-bold border-0">
                    <i class="fas fa-calendar-check me-2"></i> JADWAL PM (WEEK {{ $currentWeek }})
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($weeklyPmSchedules as $pm)
                        <a href="{{ route('pm-checks.create', $pm->id) }}?week={{ $currentWeek }}" class="list-group-item list-group-item-action p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-1 fw-bold text-success">{{ $pm->asset->fa_code ?? '-' }}</h6>
                                <span class="badge bg-light text-dark border">Week {{ $currentWeek }}</span>
                            </div>
                            <p class="mb-1 small fw-bold text-dark">{{ $pm->asset->name ?? 'Aset Tidak Ditemukan' }}</p>
                            <small class="text-muted"><i class="fas fa-play-circle me-1 text-success"></i> Mulai pengerjaan PM</small>
                        </a>
                        @empty
                        <div class="text-center py-5 text-muted small">Tidak ada jadwal PM minggu ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- TAMBAHKAN BLOK INI SEBELUM DIV TERAKHIR KAWAN --}}
    @elseif(Auth::user()->role === 'mtc')
        {{-- DASHBOARD TEKNISI --}}
        @php
            $myStats = collect($technicianStats)->firstWhere('user.id', Auth::id());
            $myAttendance = $myStats['attendance'] ?? null;
            $myActivities = $myStats['activities'] ?? collect([]);
            $myWorkMinutes = $myStats['total_work_minutes'] ?? 0;
            $myWorkload = $myStats['workload_pct'] ?? 0;
        @endphp

        <div class="row">
            <div class="col-12">
                {{-- LIVE MONITORING SECTION --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-desktop me-2 text-danger pulse"></i> LIVE MONITORING TIM TEKNISI</span>
                        <span class="badge bg-danger small">REAL-TIME</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Teknisi</th>
                                        <th>Kategori</th>
                                        <th>Aktivitas</th>
                                        <th>Mulai</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activeActivities as $activity)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                        {{ strtoupper(substr($activity->user->name ?? '?', 0, 1)) }}
                                                    </div>
                                                    <strong>{{ $activity->user->name ?? '-' }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $activity->category == 'PM' ? 'success' : ($activity->category == 'Breakdown' ? 'danger' : 'info') }}">
                                                    {{ $activity->category }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;">
                                                    {{ $activity->description }}
                                                </div>
                                            </td>
                                            <td>{{ $activity->start_time->format('H:i') }}</td>
                                            <td>
                                                <span class="badge bg-success rounded-pill pulse">
                                                    <i class="fas fa-spinner fa-spin me-1"></i> Running
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-coffee fa-2x mb-2 d-block opacity-50"></i>
                                                Tidak ada aktivitas yang sedang berjalan saat ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                {{-- HEADER --}}
                <div class="bg-primary text-white border-0 shadow-sm p-4 rounded mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="mb-1 fw-bold"><i class="fas fa-user-circle me-2"></i>Selamat Datang, {{ Auth::user()->name }}!</h4>
                            <small class="text-white-50">{{ now()->format('l, d F Y') }} | Week {{ $currentWeek }}</small>
                        </div>
                        <div>
                            @if($myAttendance)
                                <span class="badge bg-success fs-6 px-3 py-2">
                                    <i class="fas fa-clock me-1"></i> Clock In: {{ $myAttendance->clock_in->format('H:i') }}
                                </span>
                            @else
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Belum Clock In
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- STATISTIK RINGKAS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                        <h2 class="fw-bold mb-0">{{ $myWorkMinutes }}</h2>
                        <small class="text-muted">Menit Kerja Hari Ini</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-tasks fa-2x text-success mb-2"></i>
                        <h2 class="fw-bold mb-0">{{ $myActivities->count() }}</h2>
                        <small class="text-muted">Aktivitas Selesai</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h2 class="fw-bold mb-0">{{ $myWorkload }}%</h2>
                        <small class="text-muted">Workload</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- AKTIVITAS HARI INI --}}
            <div class="col-md-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold border-0 pt-3">
                        <i class="fas fa-list-alt me-2 text-primary"></i>Aktivitas Hari Ini
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Kategori</th>
                                        <th>Deskripsi</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th>Durasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($myActivities as $act)
                                    <tr>
                                        <td class="ps-3">
                                            @php $color = $act->category == 'PM' ? 'success' : ($act->category == 'Breakdown' ? 'danger' : 'info'); @endphp
                                            <span class="badge bg-{{ $color }}">{{ $act->category }}</span>
                                        </td>
                                        <td>{{ $act->description }}</td>
                                        <td>{{ $act->start_time->format('H:i') }}</td>
                                        <td>{{ $act->end_time ? $act->end_time->format('H:i') : '-' }}</td>
                                        <td>
                                            @if($act->status == 'running')
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-spinner fa-spin me-1"></i>Running
                                                </span>
                                            @else
                                                <span class="text-muted">{{ $act->duration ?? '-' }} m</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada aktivitas hari ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QUICK ACTIONS --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold border-0 pt-3">
                        <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if(!$myAttendance)
                                <form action="{{ route('monitoring.clock-in') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="shift" value="Pagi">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>Clock In
                                    </button>
                                </form>
                            @elseif(!$myAttendance->clock_out)
                                <form action="{{ route('monitoring.clock-out') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-sign-out-alt me-2"></i>Clock Out
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('tickets.index') }}" class="btn btn-primary">
                                <i class="fas fa-ticket-alt me-2"></i>Lihat Tiket
                            </a>
                            <a href="{{ route('pm.execution.index', 'weekly') }}" class="btn btn-info text-white">
                                <i class="fas fa-calendar-check me-2"></i>Jadwal PM
                            </a>
                        </div>
                    </div>
                </div>

                {{-- TIKET YANG DITUGASKAN --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark fw-bold border-0 pt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>Tiket Ditugaskan
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @php
                                $myTickets = \App\Models\Ticket::where('assigned_to', Auth::id())
                                    ->whereIn('status', ['open', 'onprogress'])
                                    ->latest()
                                    ->limit(5)
                                    ->get();
                            @endphp
                            @forelse($myTickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1 fw-bold text-primary">{{ $ticket->ticket_no }}</h6>
                                    <span class="badge bg-{{ $ticket->status == 'open' ? 'warning' : 'info' }}">
                                        {{ strtoupper($ticket->status) }}
                                    </span>
                                </div>
                                <p class="mb-0 small text-truncate">{{ $ticket->subject }}</p>
                            </a>
                            @empty
                            <div class="text-center py-4 text-muted small">Tidak ada tiket aktif.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- DASHBOARD USER BIASA --}}
        <div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
            <div class="text-center p-5 bg-white shadow-sm rounded-4 border">
                <i class="fas fa-user-circle fa-5x text-primary opacity-25 mb-4"></i>
                <h1 class="display-5 fw-bold text-dark">Welcome, {{ Auth::user()->name }}!</h1>
                <p class="lead text-muted mb-4">Silakan gunakan menu di samping untuk mengelola pekerjaanmu.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('tickets.index') }}" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-ticket-alt me-2"></i>Lihat Tiket
                    </a>
                </div>
            </div>
        </div>
    @endif {{-- PENUTUP LOGIKA ADMIN/TEKNISI/USER --}}
</div>

<style>
.pulse { animation: pulse-red 2s infinite; }
@keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
.list-group-item:hover { background-color: #f8f9fa; border-left: 4px solid #0d6efd; transition: 0.2s; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if(Auth::user()->isAdmin())
document.addEventListener('DOMContentLoaded', function() {
    // 1. Grafik Distribusi Global
    const globalCtx = document.getElementById('workChart').getContext('2d');
    new Chart(globalCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($chartData->pluck('category')) !!},
            datasets: [{
                data: {!! json_encode($chartData->pluck('total_minutes')) !!},
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b'],
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 2. Looping Grafik Per Teknisi
    @foreach($technicianStats as $stat)
        const ctx{{ $stat['user']->id }} = document.getElementById('chart-{{ $stat['user']->id }}').getContext('2d');
        new Chart(ctx{{ $stat['user']->id }}, {
            type: 'pie',
            data: {
                labels: {!! json_encode($stat['chart_labels']) !!},
                datasets: [{
                    data: {!! json_encode($stat['chart_data']) !!},
                    backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b']
                }]
            },
            options: { 
                plugins: { legend: { display: false } },
                maintainAspectRatio: false 
            }
        });
    @endforeach
});
@endif
</script>
@endpush