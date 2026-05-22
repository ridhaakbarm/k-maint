@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Tickets</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        {{-- Tombol Buat Ticket dengan pengecekan jumlah status request_to_close --}}
        @php
            $requestToCloseCount = $tickets->where('status', 'request_to_close')->where('requester_id', Auth::id())->count();
            $canCreateTicket = $requestToCloseCount < 5; // Bisa buat tiket jika kurang dari 5
        @endphp
        
        @if(!$canCreateTicket)
            <button type="button" class="btn btn-secondary" disabled 
                    style="cursor: not-allowed;" 
                    title="Anda memiliki {{ $requestToCloseCount }} tiket dengan status Request to Close (maksimal 5). Selesaikan beberapa tiket tersebut terlebih dahulu sebelum membuat tiket baru.">
                <i class="fas fa-plus"></i> Buat Ticket Baru
            </button>
        @else
            <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Ticket Baru
            </a>
        @endif
    </div>
</div>

{{-- Alert peringatan jika jumlah request_to_close mendekati batas --}}
@if($requestToCloseCount >= 3 && $requestToCloseCount < 5)
<div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Perhatian!</strong> Anda memiliki <strong>{{ $requestToCloseCount }} tiket</strong> dengan status <strong>Request to Close</strong>. 
    Maksimal tiket yang dapat Anda miliki adalah 5. Segera selesaikan tiket-tiket tersebut.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@elseif($requestToCloseCount >= 5)
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-ban me-2"></i>
    <strong>Batasan Tercapai!</strong> Anda memiliki <strong>{{ $requestToCloseCount }} tiket</strong> dengan status <strong>Request to Close</strong>. 
    Anda <strong>TIDAK DAPAT</strong> membuat tiket baru karena sudah mencapai batas maksimal 5 tiket.
    <br>
    <small class="mt-1 d-block">Silahkan selesaikan (close) beberapa tiket terlebih dahulu.</small>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

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

{{-- Quick Filter Buttons untuk Admin, GA, dan MTC --}}
@if(Auth::user()->isAdmin() || Auth::user()->isGA() || Auth::user()->isMTC())
<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body py-2">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="fw-bold me-3 mb-2 mb-md-0"><i class="fas fa-filter me-1"></i> Filter Cepat:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('tickets.index') }}" class="btn btn-primary">Semua</a>
                        <a href="{{ route('tickets.index') }}?status=open" class="btn btn-outline-primary">Open</a>
                        <a href="{{ route('tickets.index') }}?status=onprogress" class="btn btn-outline-warning text-dark">On Progress</a>
                        <a href="{{ route('tickets.index') }}?status=pending" class="btn btn-outline-success">Pending</a>
                        <a href="{{ route('tickets.index') }}?status=request_to_close" class="btn btn-outline-info text-dark">Request to Close</a>
                        <a href="{{ route('tickets.index') }}?status=closed" class="btn btn-outline-success">Closed</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Daftar Semua Tickets</h5>
        @if($requestToCloseCount > 0)
        <span class="badge bg-warning ms-2">
            <i class="fas fa-clock"></i> {{ $requestToCloseCount }}/5 Tiket Request to Close
        </span>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="ticketsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Ticket No</th>
                        <th>Mesin (Asset)</th>
                        <th>Subject</th>
                        <th>Requester</th>
                        <th>Status</th>
                        <th>Tgl Request</th>
                        <th>Assignment</th>
                        <th>Durasi</th>
                        <th>Follow Up</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr class="{{ ($ticket->status == 'request_to_close' && $ticket->requester_id == Auth::id()) ? 'table-warning' : '' }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $ticket->ticket_no }}
                            </span>
                        </td>
                        <td>
                            @if($ticket->asset)
                                <strong class="text-primary">{{ $ticket->asset->name }}</strong>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        
                        <td>
                            <div class="fw-bold">{{ Str::limit($ticket->subject, 30) }}</div>
                        </td>
                        <td>
                            <small>
                                <strong>{{ $ticket->requester->name }}</strong><br>
                                <span class="text-muted" style="font-size: 0.75rem;">{{ $ticket->requester->department }}</span>
                            </small>
                        </td>
                        <td>
                            <span class="badge
                                @if($ticket->status == 'open') bg-danger
                                @elseif($ticket->status == 'onprogress') bg-warning text-dark
                                @elseif($ticket->status == 'schedule') bg-secondary
                                @elseif($ticket->status == 'request_to_close') bg-info text-dark
                                @elseif($ticket->status == 'rejected') bg-dark
                                @else bg-success @endif">
                                {{ ucfirst($ticket->status) }}
                            </span>

                            {{-- TANDA JEDA (Tiket onprogress yang sedang dihentikan) --}}
                            @if(method_exists($ticket, 'isPaused') && $ticket->isPaused())
                                <span class="badge bg-dark mt-1 d-block shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fas fa-pause-circle me-1"></i>JEDA
                                </span>
                            @endif

                            {{-- TANDA KHUSUS JIKA TIKET PERNAH DI-REJECT USER --}}
                            @if($ticket->was_rejected)
                                <span class="badge bg-danger mt-1 d-block shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>REJECTED TICKET
                                </span>
                            @endif

                            {{-- TANDA KHUSUS UNTUK TIKET REQUEST_TO_CLOSE MILIK USER YANG LOGIN --}}
                            @if($ticket->status == 'request_to_close' && $ticket->requester_id == Auth::id())
                                <span class="badge bg-warning mt-1 d-block shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fas fa-hourglass-half me-1"></i>MENUNGGU KONFIRMASI
                                </span>
                            @endif
                        </td>
                        <td><small>{{ $ticket->request_date->format('d/m/Y') }}</small></td>
                        
                        <td>
                            {{-- Jika sudah dikerjakan/dijadwalkan, munculkan nama teknisinya --}}
                            @if(in_array($ticket->status, ['onprogress', 'schedule', 'request_to_close', 'closed']) && $ticket->assigned_to)
                                <div class="small fw-bold text-dark">
                                    <i class="fas fa-user-gear me-1 text-primary"></i>{{ $ticket->assigned_to }}
                                </div>
                            @elseif($ticket->assigned_types)
                                {{-- Jika masih Open, cukup munculkan jenis unitnya saja --}}
                                @foreach($ticket->assigned_types as $type)
                                    <span class="badge bg-outline-secondary border text-dark" style="font-size: 0.65rem;">
                                        {{ strtoupper($type) }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>
                            @php
                                $startTime = $ticket->created_at;
                                $endTime = ($ticket->status == 'closed' && $ticket->closed_date) ? $ticket->closed_date : now();
                                $diff = $startTime->diff($endTime);
                                $days = $diff->d;
                                $hours = $diff->h;
                                $minutes = $diff->i;
                            @endphp

                            <div class="d-flex flex-column">
                                <span class="fw-bold {{ $ticket->status == 'closed' ? 'text-success' : 'text-danger' }}" style="font-size: 0.85rem;">
                                    <i class="fas fa-clock me-1"></i>
                                    @if($days > 0) {{ $days }}h @endif
                                    {{ $hours }}j {{ $minutes }}m
                                </span>
                                
                                @if($ticket->status != 'closed')
                                    <small class="text-muted" style="font-size: 0.7rem;">(Sedang Berjalan...)</small>
                                @else
                                    <small class="text-muted" style="font-size: 0.7rem;">(Selesai)</small>
                                @endif
                            </div>
                        </td>

                        {{-- Tanggal Follow Up --}}
                        <td>
                            @if($ticket->estimated_date)
                                <div class="d-flex flex-column">
                                    <span class="fw-bold {{ \Carbon\Carbon::parse($ticket->estimated_date)->isPast() ? 'text-danger' : 'text-primary' }}" style="font-size: 0.85rem;">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        {{ \Carbon\Carbon::parse($ticket->estimated_date)->format('d/m/Y') }}
                                    </span>
                                    @if(\Carbon\Carbon::parse($ticket->estimated_date)->isPast())
                                        <small class="text-danger" style="font-size: 0.7rem;">
                                            <i class="fas fa-exclamation-circle"></i> Lewat Tgl
                                        </small>
                                    @else
                                        @php
                                            $daysUntil = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($ticket->estimated_date), false);
                                        @endphp
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            {{ $daysUntil > 0 ? "{$daysUntil} hari lagi" : 'Hari ini' }}
                                        </small>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted" style="font-size: 0.8rem;">-</span>
                            @endif
                        </td>

                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-outline-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if(Auth::user()->isAdmin())
                                <button type="button" class="btn btn-outline-danger delete-ticket" 
                                        data-ticket-id="{{ $ticket->id }}" 
                                        data-ticket-no="{{ $ticket->ticket_no }}"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
@if(Auth::user()->isAdmin())
<div class="modal fade" id="deleteTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus ticket <strong id="ticketNoToDelete"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteTicketForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // 1. Inisialisasi DataTable
        const table = $('#ticketsTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/Indonesian.json" },
            "order": [[1, 'desc']], 
            "pageLength": 10,
            "scrollX": true 
        });

        // 2. Delete ticket functionality (Admin only)
        $('#ticketsTable').on('click', '.delete-ticket', function() {
            const ticketId = $(this).data('ticket-id');
            const ticketNo = $(this).data('ticket-no');
            
            $('#ticketNoToDelete').text(ticketNo);
            const deleteUrl = '{{ route("tickets.destroy", ":id") }}'.replace(':id', ticketId);
            $('#deleteTicketForm').attr('action', deleteUrl);
            
            $('#deleteTicketModal').modal('show');
        });
    });
</script>
@endpush