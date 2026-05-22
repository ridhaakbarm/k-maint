@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Daftar Bagian Mesin</h2>
        <a href="{{ route('machine_parts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Bagian Mesin
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="machinePartsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Bagian</th>
                        <th>Asset (FA Code)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($machineParts as $part)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $part->name }}</td>
                        <td>
                            <span class="badge bg-info text-dark">
                                {{ $part->asset->name ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('machine_parts.edit', $part->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('machine_parts.destroy', $part->id) }}" method="POST" onsubmit="return confirm('Hapus bagian ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
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