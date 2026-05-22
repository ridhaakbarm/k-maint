@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail User: {{ $user->name }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasi User</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Username</th>
                        <td>{{ $user->username }}</td>
                    </tr>
                    <tr>
                        <th>Nama Lengkap</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                         <tr>
        <th>WhatsApp</th>
        <td>
            @if($user->whatsapp)
                {{ $user->whatsapp }}
                <a href="https://wa.me/{{ $user->whatsapp }}" target="_blank" class="btn btn-sm btn-success ms-2">
                    <i class="fab fa-whatsapp"></i> Chat
                </a>
            @else
                <span class="text-muted">Belum diisi</span>
            @endif
        </td>
    </tr>
                        <th>Department</th>
                        <td>{{ $user->department }}</td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td>
                            <span class="badge 
                                @if($user->role == 'admin') bg-danger
                                @elseif($user->role == 'ga') bg-warning
                                @else bg-primary @endif">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal Dibuat</th>
                        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Diupdate</th>
                        <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- User's Tickets Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Statistik Tickets</h5>
            </div>
            <div class="card-body">
                @php
                    $userStats = \App\Models\Ticket::getStatistics($user->id);
                @endphp
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4>{{ $userStats['total'] }}</h4>
                        <small class="text-muted">Total Tickets</small>
                    </div>
                    <div class="col-md-3">
                        <h4>{{ $userStats['open'] }}</h4>
                        <small class="text-muted">Open</small>
                    </div>
                    <div class="col-md-3">
                        <h4>{{ $userStats['onprogress'] }}</h4>
                        <small class="text-muted">On Progress</small>
                    </div>
                    <div class="col-md-3">
                        <h4>{{ $userStats['closed'] }}</h4>
                        <small class="text-muted">Closed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit User
                    </a>
                    @if($user->id !== auth()->id())
                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-grid">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                            <i class="fas fa-trash"></i> Hapus User
                        </button>
                    </form>
                    @else
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-trash"></i> Tidak dapat hapus akun sendiri
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasi Role</h5>
            </div>
            <div class="card-body">
                @if($user->role == 'admin')
                <div class="alert alert-danger">
                    <h6><i class="fas fa-shield-alt"></i> Admin</h6>
                    <p class="mb-0">Akses penuh ke semua fitur sistem termasuk management user.</p>
                </div>
                @elseif($user->role == 'ga')
                <div class="alert alert-warning">
                    <h6><i class="fas fa-tools"></i> General Affair</h6>
                    <p class="mb-0">Dapat mengelola semua tickets dan update status.</p>
                </div>
                @else
                <div class="alert alert-primary">
                    <h6><i class="fas fa-user"></i> User</h6>
                    <p class="mb-0">Hanya dapat membuat dan melihat ticket sendiri.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection