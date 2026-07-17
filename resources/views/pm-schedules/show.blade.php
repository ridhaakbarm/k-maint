@extends('layouts.app')

@section('page_title', 'Detail Jadwal PM')
@section('breadcrumb', 'Detail Jadwal PM')

@section('content')
<div class="row">
    {{-- Alert Messages --}}
    <div class="col-12">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <div class="col-md-8">
        {{-- INFORMASI UTAMA --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Informasi Jadwal PM</h5>
                <span class="badge bg-{{ $pmSchedule->is_active ? 'success' : 'danger' }}">
                    {{ $pmSchedule->is_active ? 'AKTIF' : 'NON-AKTIF' }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%" class="bg-light">Mesin (Asset)</th>
                        <td>
                            <strong>{{ $pmSchedule->asset->name ?? '-' }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Tipe Jadwal</th>
                        <td>
                            @if($pmSchedule->schedule_type == 'daily')
                                <span class="badge bg-success">
                                    <i class="fas fa-calendar-day me-1"></i> Harian (Daily Check)
                                </span>
                            @elseif($pmSchedule->schedule_type == 'weekly')
                                <span class="badge bg-info text-dark">
                                    <i class="fas fa-sync-alt me-1"></i> Rutin (Weekly Check)
                                </span>
                            @elseif($pmSchedule->schedule_type == 'yearly')
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-calendar-check me-1"></i> Major (Yearly Check)
                                </span>
                            @else
                                <span class="badge bg-secondary">{{ strtoupper($pmSchedule->schedule_type) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Nama Jadwal</th>
                        <td>{{ str_replace('FA - ', '', $pmSchedule->name) }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Deskripsi</th>
                        <td>{{ $pmSchedule->description ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Dibuat / Diupdate</th>
                        <td>{{ $pmSchedule->created_at->format('d/m/Y') }} / {{ $pmSchedule->updated_at->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('pm.schedule.edit', $pmSchedule->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Jadwal
                </a>
                <a href="{{ route('pm.schedule.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                
                {{-- Tombol untuk Teknisi MTC --}}
                @if(Auth::user()->isMTC() && $pmSchedule->is_active)
                <a href="{{ route('pm-checks.create', $pmSchedule->id) }}" class="btn btn-primary float-end">
                    <i class="fas fa-play"></i> Mulai Cek PM
                </a>
                @endif
            </div>
        </div>

        {{-- DAFTAR TEMPLATE CHECKLIST --}}
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Template Checklist Items</h5>
                
                <div class="card-tools">
                    {{-- Tombol Import Excel --}}
                    <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modal-import">
                        <i class="fas fa-file-excel"></i> Import Excel
                    </button>

                    {{-- Tombol Tambah Manual --}}
                    <a href="{{ route('pm.templates.create') }}?pm_schedule_id={{ $pmSchedule->id }}" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Item
                    </a>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60" class="text-center">Urutan</th>
                                <th>Item Checklist</th>
                                <th>Bagian</th>
                                <th>Minggu Aktif</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="80" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pmSchedule->checklistTemplates->sortBy('order') as $template)
                            <tr>
                                <td class="text-center">{{ $template->order }}</td>
                                <td>
                                    <strong>{{ $template->item_name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($template->check_standard, 50) }}</small>
                                </td>
                                <td>{{ $template->checked_part }}</td>
                                
                                <td>
                                    @if(is_array($template->active_weeks))
                                        @if(count($template->active_weeks) >= 52)
                                            <span class="badge bg-success">Setiap Minggu</span>
                                        @elseif(count($template->active_weeks) == 0)
                                            <span class="badge bg-danger">Belum Diatur</span>
                                        @else
                                            <small class="text-muted d-block" style="max-width: 150px; white-space: normal;">
                                                W: {{ implode(', ', $template->active_weeks) }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Aktif' : 'Non' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('pm.templates.edit', $template->id) }}"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('pm.templates.destroy', $template->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus item checklist ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-file-excel fa-3x mb-3 text-success"></i><br>
                                        <h5>Belum ada item checklist</h5>
                                        <p>Gunakan tombol <strong>Import Excel</strong> untuk upload data secara masal.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- INFORMASI MESIN --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-bold">Informasi Aset</div>
            <div class="card-body">
                <p class="mb-3"><strong>Nama Mesin:</strong> {{ $pmSchedule->asset->name ?? '-' }}</p>
                <hr>
                <a href="{{ route('assets.index') }}" class="btn btn-outline-info btn-sm w-100">
                    <i class="fas fa-list"></i> Lihat Daftar Aset
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-3 bg-light text-center p-3">
            <div class="text-muted small text-uppercase fw-bold">Total Item Checklist</div>
            <h2 class="fw-bold text-dark mb-0">{{ $pmSchedule->checklistTemplates->count() }}</h2>
        </div>
        
        {{-- AKSI CEPAT --}}
        @if(Auth::user()->isAdmin() || Auth::user()->isMTC())
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white fw-bold">Aksi Cepat</div>
            <div class="card-body">
                <form action="{{ route('pm.schedule.toggle-status', $pmSchedule->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-{{ $pmSchedule->is_active ? 'outline-danger' : 'success' }} w-100 fw-bold">
                        <i class="fas fa-power-off me-1"></i> 
                        {{ $pmSchedule->is_active ? 'Nonaktifkan Jadwal' : 'Aktifkan Jadwal' }}
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- MODAL IMPORT EXCEL --}}
<div class="modal fade" id="modal-import" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('pm.templates.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="pm_schedule_id" value="{{ $pmSchedule->id }}">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i>Import Checklist Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File Excel (.xlsx / .csv)</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i> <strong>Panduan Format:</strong><br>
                        Gunakan header berikut pada baris pertama:<br>
                        <code>item_name</code>, <code>checked_part</code>, <code>instructions</code>, <code>check_standard</code>, <code>weeks</code>
                    </div>

                    <div class="bg-light p-2 rounded border small">
                        <strong>Tips Kolom 'weeks':</strong>
                        <ul class="mb-0 ps-3">
                            <li><code>1-52</code> (Rutin tiap minggu)</li>
                            <li><code>4,8,12,16</code> (Setiap bulan)</li>
                            <li><code>12,24,36,48</code> (Setiap 3 bulan)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4"><i class="fas fa-upload me-1"></i> Import Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
