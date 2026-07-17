@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Daftar Template Checklist PM</h3>
                <div>
                    <a href="{{ route('pm.templates.export') }}" class="btn btn-success btn-sm me-1">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    @if(auth()->user()->username === 'andre')
                    <a href="{{ route('pm.templates.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Template
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success"><i class="fas fa-check me-2"></i>{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger"><i class="fas fa-ban me-2"></i>{{ session('error') }}</div>
                @endif

                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Aset & Jadwal</th>
                            <th>Item Checklist</th>
                            <th>Bagian Dicek</th>
                            <th>Instruksi</th>
                            <th>Standar</th>
                            <th>Minggu Aktif</th>
                            <th width="100">Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checklistTemplates as $template)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $template->pmSchedule->asset->name ?? 'Aset Tidak Ditemukan' }}</strong><br>
                                <small class="text-muted">{{ $template->pmSchedule->name ?? '-' }}</small>
                            </td>
                            <td>{{ $template->item_name }}</td>
                            <td>{{ $template->checked_part }}</td>
                            <td>{{ Str::limit($template->instructions, 50) }}</td>
                            <td>{{ Str::limit($template->check_standard, 50) }}</td>
                            <td>
                                @if($template->active_weeks)
                                    <small>{{ Str::limit(implode(', ', $template->active_weeks), 30) }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $template->is_active ? 'success' : 'danger' }}">
                                    {{ $template->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('pm.templates.show', $template->id) }}" class="btn btn-outline-info"><i class="fas fa-eye"></i></a>
                                    @if(auth()->user()->username === 'andre')
                                    <a href="{{ route('pm.templates.edit', $template->id) }}" class="btn btn-outline-warning"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('pm.templates.destroy', $template->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Hapus template ini?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4 text-muted">Data template kosong.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection