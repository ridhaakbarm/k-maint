@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Vendor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('vendors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Vendor
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Daftar Semua Vendor</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="vendorsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama Vendor</th>
                        <th>Nama PT/CV</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $vendor->nama_vendor }}</strong>
                        </td>
                        <td>{{ $vendor->nama_pt_cv }}</td>
                        <td>
                            <span class="badge {{ $vendor->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $vendor->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        <td>{{ $vendor->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger delete-vendor" 
                                        data-vendor-id="{{ $vendor->id }}" 
                                        data-vendor-name="{{ $vendor->nama_vendor }}"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteVendorModal" tabindex="-1" aria-labelledby="deleteVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteVendorModalLabel">Konfirmasi Hapus Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus vendor <strong id="vendorNameToDelete"></strong>?</p>
                <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteVendorForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus Vendor</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#vendorsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/Indonesian.json"
            },
            "order": [[1, 'asc']]
        });

        // Delete Vendor Confirmation
        $('.delete-vendor').on('click', function() {
            const vendorId = $(this).data('vendor-id');
            const vendorName = $(this).data('vendor-name');
            
            $('#vendorNameToDelete').text(vendorName);
            $('#deleteVendorForm').attr('action', '{{ route("vendors.destroy", "") }}/' + vendorId);
            $('#deleteVendorModal').modal('show');
        });
    });
</script>
@endpush