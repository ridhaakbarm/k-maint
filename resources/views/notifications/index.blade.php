@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Notifikasi</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- Filter Tabs -->
        <div class="btn-group me-2" role="group">
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
               class="btn btn-outline-primary {{ $filter == 'unread' ? 'active' : '' }}">
                <i class="fas fa-envelope"></i> Belum Dibaca
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
               class="btn btn-outline-secondary {{ $filter == 'read' ? 'active' : '' }}">
                <i class="fas fa-envelope-open"></i> Sudah Dibaca
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
               class="btn btn-outline-info {{ $filter == 'all' ? 'active' : '' }}">
                <i class="fas fa-list"></i> Semua
            </a>
        </div>
        
        @if($notifications->count() > 0 && $filter != 'read')
        <form method="POST" action="{{ route('notifications.markAllAsRead') }}" class="ms-2">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
            </button>
        </form>
        @endif
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    @php
        $totalCount = Auth::user()->notifications()->count();
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $readCount = $totalCount - $unreadCount;
    @endphp
    <div class="col-md-4">
        <div class="card border-left-primary shadow">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Notifikasi</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCount }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-bell fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-warning shadow">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Belum Dibaca</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $unreadCount }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-success shadow">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Sudah Dibaca</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $readCount }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope-open fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    @if($filter == 'unread')
                        <i class="fas fa-envelope text-warning"></i> Notifikasi Belum Dibaca
                    @elseif($filter == 'read')
                        <i class="fas fa-envelope-open text-success"></i> Notifikasi Sudah Dibaca
                    @else
                        <i class="fas fa-list text-info"></i> Semua Notifikasi
                    @endif
                </h5>
                <span class="badge bg-secondary">{{ $notifications->total() }} items</span>
            </div>
            <div class="card-body">
                @if($notifications->count() > 0)
                <div class="list-group">
                    @foreach($notifications as $notification)
                    <div class="list-group-item list-group-item-action {{ $notification->is_read ? '' : 'list-group-item-warning' }}">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-grow-1 me-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        @if(!$notification->is_read)
                                        <i class="fas fa-circle text-primary me-1" style="font-size: 8px;"></i>
                                        @endif
                                        {{ $notification->message }}
                                    </h6>
                                    <small>{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-ticket-alt me-1"></i>Ticket: {{ $notification->ticket->ticket_no }} - 
                                        Status: 
                                        <span class="badge 
                                            @if($notification->ticket->status == 'open') bg-primary
                                            @elseif($notification->ticket->status == 'onprogress') bg-warning
                                            @elseif($notification->ticket->status == 'request_to_close') bg-info
                                            @else bg-success @endif">
                                            {{ ucfirst($notification->ticket->status) }}
                                        </span>
                                    </small>
                                </p>
                                @if(!$notification->is_read)
                                <small class="text-primary">
                                    <i class="fas fa-circle"></i> Belum dibaca
                                </small>
                                @else
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i> Sudah dibaca - {{ $notification->updated_at->diffForHumans() }}
                                </small>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('notifications.show', $notification) }}" 
                                       class="btn btn-outline-primary" title="Lihat Ticket">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$notification->is_read)
                                    <form action="{{ route('notifications.markAsRead', $notification) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success" title="Tandai Sudah Dibaca">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            Menampilkan {{ $notifications->firstItem() }} - {{ $notifications->lastItem() }} dari {{ $notifications->total() }} notifikasi
                        </p>
                    </div>
                    <div>
                        {{ $notifications->appends(['filter' => $filter])->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">
                        @if($filter == 'unread')
                            Tidak ada notifikasi belum dibaca
                        @elseif($filter == 'read')
                            Tidak ada notifikasi sudah dibaca
                        @else
                            Belum ada notifikasi
                        @endif
                    </p>
                    @if($filter != 'all')
                    <a href="{{ route('notifications.index', ['filter' => 'all']) }}" class="btn btn-outline-primary">
                        Lihat Semua Notifikasi
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.list-group-item-warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Mark as read without page refresh
    $('form.mark-as-read-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const notificationItem = form.closest('.list-group-item');
        const url = form.attr('action');
        
        $.post(url, {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                // Remove the warning styling
                notificationItem.removeClass('list-group-item-warning');
                
                // Update the status text
                notificationItem.find('.text-primary').html(
                    '<i class="fas fa-check-circle"></i> Sudah dibaca - Baru saja'
                );
                
                // Remove the mark as read button
                form.remove();
                
                // Update badge count if on unread filter
                if ('{{ $filter }}' === 'unread') {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            }
        })
        .fail(function(xhr) {
            alert('Gagal menandai notifikasi sebagai sudah dibaca');
        });
    });
    
    // Auto-mark as read when clicking view (if not already read)
    $('.list-group-item:not(.list-group-item-warning) a[href*="notifications"]').on('click', function(e) {
        const notificationItem = $(this).closest('.list-group-item');
        if (notificationItem.hasClass('list-group-item-warning')) {
            const markAsReadUrl = notificationItem.find('form').attr('action');
            if (markAsReadUrl) {
                $.post(markAsReadUrl, {
                    _token: '{{ csrf_token() }}'
                });
            }
        }
    });
});
</script>
@endpush