@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3">
                <h3 class="card-title mb-0 fw-bold text-dark">Daftar Jadwal Preventive Maintenance</h3>
                <a href="{{ route('pm.schedule.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Tambah Jadwal PM
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    @foreach($scheduleTypes as $type => $label)
                        @php
                            $count = $pmSchedules->where('schedule_type', $type)->count();
                            $activeCount = $pmSchedules->where('schedule_type', $type)->where('is_active', true)->count();
                            $color = ['daily' => 'success', 'weekly' => 'info', 'yearly' => 'warning'][$type] ?? 'secondary';
                        @endphp
                        <div class="col-md-4">
                            <div class="pm-type-summary border-start border-4 border-{{ $color }}">
                                <div class="small text-muted text-uppercase fw-bold">{{ $label }}</div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div class="h4 mb-0 fw-bold">{{ $count }}</div>
                                    <span class="badge bg-{{ $color }}">{{ $activeCount }} aktif</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Nama Mesin / Aset</th>
                                <th>Tipe</th>
                                <th>Nama Jadwal</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pmSchedules as $schedule)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{-- FA Code dihapus, Nama Mesin dibuat tebal dan berwarna biru --}}
                                    <strong class="text-primary">{{ $schedule->asset->name ?? 'Aset Tidak Ditemukan' }}</strong>
                                </td>
                                <td>
                                    @php
                                        $typeColor = ['daily' => 'success', 'weekly' => 'info', 'yearly' => 'warning'][$schedule->schedule_type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $typeColor }}">
                                        {{ strtoupper($schedule->schedule_type) }}
                                    </span>
                                </td>
                                <td>{{ str_replace('FA - ', '', $schedule->name) }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-{{ $schedule->is_active ? 'success' : 'danger' }}">
                                        {{ $schedule->is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pm.schedule.show', $schedule->id) }}" class="btn btn-outline-info" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('pm.schedule.edit', $schedule->id) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('pm.schedule.destroy', $schedule->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Hapus jadwal ini?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted italic">
                                    <i class="fas fa-calendar-times fa-2x mb-3 d-block"></i>
                                    Belum ada jadwal PM yang terdaftar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table thead th { border-top: none; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge { font-weight: 600; padding: 0.5em 0.8em; }
    .pm-type-summary { background: #f8f9fa; border-radius: 8px; padding: 14px 16px; }
</style>
@endsection
