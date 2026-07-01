@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-0">Tiket Internal</h1>
        <small class="text-muted">Pekerjaan lanjutan dari temuan PM atau instruksi internal Maintenance/Engineering.</small>
    </div>
    <a href="{{ route('internal-tickets.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Tiket Internal
    </a>
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

<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body py-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-bold me-2"><i class="fas fa-filter me-1"></i> Filter Cepat:</span>
                    <a href="{{ route('internal-tickets.index') }}" class="btn btn-sm btn-primary">Semua</a>
                    <a href="{{ route('internal-tickets.index', ['status' => 'open']) }}" class="btn btn-sm btn-outline-danger">Open</a>
                    <a href="{{ route('internal-tickets.index', ['status' => 'onprogress']) }}" class="btn btn-sm btn-outline-warning text-dark">On Progress</a>
                    <a href="{{ route('internal-tickets.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-secondary">Pending</a>
                    <a href="{{ route('internal-tickets.index', ['status' => 'closed']) }}" class="btn btn-sm btn-outline-success">Closed</a>
                    <a href="{{ route('internal-tickets.index', ['source_type' => 'pm']) }}" class="btn btn-sm btn-outline-info text-dark">Dari PM</a>
                    <a href="{{ route('internal-tickets.index', ['source_type' => 'lisan']) }}" class="btn btn-sm btn-outline-dark">Instruksi Lisan</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Daftar Tiket Internal</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="internalTicketsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>No Tiket</th>
                        <th>Sumber</th>
                        <th>Mesin</th>
                        <th>Subject</th>
                        <th>Pembuat</th>
                        <th>PIC</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $ticket->ticket_no }}</span></td>
                        <td>
                            @if($ticket->source_type === 'pm')
                                <span class="badge bg-info text-dark">Temuan PM</span>
                            @elseif($ticket->source_type === 'lisan')
                                <span class="badge bg-dark">Instruksi Lisan</span>
                            @else
                                <span class="badge bg-secondary">Temuan Lain</span>
                            @endif
                            <span class="badge
                                @if($ticket->priority === 'urgent') bg-danger
                                @elseif($ticket->priority === 'high') bg-warning text-dark
                                @elseif($ticket->priority === 'low') bg-secondary
                                @else bg-primary @endif">
                                {{ strtoupper($ticket->priority) }}
                            </span>
                        </td>
                        <td><strong>{{ $ticket->asset->name ?? $ticket->pmCheckItem?->pmCheck?->pmSchedule?->asset?->name ?? '-' }}</strong></td>
                        <td>
                            <div class="fw-bold">{{ Str::limit($ticket->subject, 40) }}</div>
                            <small class="text-muted">{{ $ticket->request_date->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <strong>{{ $ticket->requester->name ?? '-' }}</strong><br>
                            <small class="text-muted">{{ $ticket->requester->department ?? '-' }}</small>
                        </td>
                        <td>{{ $ticket->assigned_to_name ?? '-' }}</td>
                        <td>
                            @if($ticket->target_date)
                                <span class="{{ $ticket->target_date->isPast() && $ticket->status !== 'closed' ? 'text-danger fw-bold' : '' }}">
                                    {{ $ticket->target_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge
                                @if($ticket->status === 'open') bg-danger
                                @elseif($ticket->status === 'onprogress') bg-warning text-dark
                                @elseif($ticket->status === 'pending') bg-secondary
                                @else bg-success @endif">
                                {{ strtoupper($ticket->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('internal-tickets.show', $ticket) }}" class="btn btn-outline-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(Auth::user()->isAdmin())
                                    <button type="button" class="btn btn-outline-danger delete-internal-ticket"
                                            data-ticket-id="{{ $ticket->id }}"
                                            data-ticket-no="{{ $ticket->ticket_no }}">
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

@if(Auth::user()->isAdmin())
<div class="modal fade" id="deleteInternalTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Hapus tiket internal <strong id="internalTicketNoToDelete"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteInternalTicketForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
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
    $('#internalTicketsTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/Indonesian.json" },
        "order": [[1, 'desc']],
        "pageLength": 10,
        "scrollX": true
    });

    $('#internalTicketsTable').on('click', '.delete-internal-ticket', function() {
        const ticketId = $(this).data('ticket-id');
        $('#internalTicketNoToDelete').text($(this).data('ticket-no'));
        $('#deleteInternalTicketForm').attr('action', '{{ route("internal-tickets.destroy", ":id") }}'.replace(':id', ticketId));
        $('#deleteInternalTicketModal').modal('show');
    });
});
</script>
@endpush
