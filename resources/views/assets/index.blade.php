@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark">Manajemen Mesin (Aset)</h1>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-excel me-1"></i> Import Excel
        </button>
        <a href="{{ route('assets.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Tambah Mesin Baru
        </a>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-import me-2"></i>Import Data Mesin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Pastikan header Excel adalah: <br>
                        <strong>equipment, type, equip_tag, location</strong>
                    </div>
                    <label class="form-label fw-bold">Pilih File Excel / CSV</label>
                    <input type="file" name="file_excel" class="form-control form-control-lg" required>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Proses Import</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-muted"><i class="fas fa-list me-2"></i>Data Mesin Aktif</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="assetsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3" width="50">No.</th>
                        <th>Equipment</th>
                        <th>Type</th>
                        <th>Equip Tag</th>
                        <th>Location</th>
                        <th width="120" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assets as $asset)
                    <tr>
                        <td class="ps-3">{{ $loop->iteration }}</td>
                        <td>
                            <strong class="text-primary">{{ $asset->name }}</strong>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $asset->type ?? '-' }}</span></td>
                        <td>{{ $asset->equip_tag ?? '-' }}</td>
                        <td><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>{{ $asset->location ?? '-' }}</small></td>
                        <td class="text-center pe-3">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('assets.destroy', $asset) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus mesin ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#assetsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/Indonesian.json"
        },
        "pageLength": 10,
        "ordering": true,
        "columnDefs": [
            { "orderable": false, "targets": 5 } // Kolom Aksi jangan bisa di-sorting
        ]
    });
});
</script>
@endpush