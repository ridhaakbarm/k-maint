@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail Vendor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Informasi Vendor</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Nama Vendor</th>
                        <td>{{ $vendor->nama_vendor }}</td>
                    </tr>
                    <tr>
                        <th>Nama PT/CV</th>
                        <td>{{ $vendor->nama_pt_cv }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge {{ $vendor->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $vendor->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal Dibuat</th>
                        <td>{{ $vendor->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Update</th>
                        <td>{{ $vendor->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Vendor
            </a>
            <a href="{{ route('vendors.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> Lihat Semua Vendor
            </a>
        </div>
    </div>
</div>
@endsection