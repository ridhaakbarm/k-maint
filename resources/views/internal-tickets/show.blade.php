@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-0">Detail Tiket Internal: {{ $ticket->ticket_no }}</h1>
        <small class="text-muted">Rekam pengerjaan internal Maintenance/Engineering.</small>
    </div>
    <a href="{{ route('internal-tickets.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
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

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Informasi Tiket</h5>
                <span class="badge
                    @if($ticket->status === 'open') bg-danger
                    @elseif($ticket->status === 'onprogress') bg-warning text-dark
                    @elseif($ticket->status === 'pending') bg-secondary
                    @else bg-success @endif">
                    {{ strtoupper($ticket->status) }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%" class="bg-light">No Tiket</th>
                        <td class="fw-bold text-primary">{{ $ticket->ticket_no }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Sumber</th>
                        <td>
                            @if($ticket->source_type === 'pm')
                                <span class="badge bg-info text-dark">Temuan PM</span>
                            @elseif($ticket->source_type === 'lisan')
                                <span class="badge bg-dark">Instruksi Lisan Leader</span>
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
                    </tr>
                    <tr>
                        <th class="bg-light">Asset / Mesin</th>
                        <td>{{ $ticket->asset->name ?? $ticket->pmCheckItem?->pmCheck?->pmSchedule?->asset?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Tanggal Request</th>
                        <td>{{ $ticket->request_date->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Pembuat</th>
                        <td>{{ $ticket->requester->name ?? '-' }} ({{ $ticket->requester->department ?? '-' }})</td>
                    </tr>
                    <tr>
                        <th class="bg-light">PIC</th>
                        <td>{{ $ticket->assigned_to_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Target Selesai</th>
                        <td>{{ $ticket->target_date ? $ticket->target_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Subject</th>
                        <td><strong>{{ $ticket->subject }}</strong></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Deskripsi / Temuan</th>
                        <td>{!! nl2br(e($ticket->description)) !!}</td>
                    </tr>
                    @if($ticket->work_result)
                    <tr>
                        <th class="bg-light">Hasil Pengerjaan</th>
                        <td>{!! nl2br(e($ticket->work_result)) !!}</td>
                    </tr>
                    @endif
                    @if($ticket->pmCheckItem)
                    <tr>
                        <th class="bg-light">Referensi PM</th>
                        <td>
                            <a href="{{ route('pm.execution.show', $ticket->pmCheckItem->pm_check_id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt me-1"></i>Lihat PM Check
                            </a>
                            <div class="small text-muted mt-2">
                                Item: {{ $ticket->pmCheckItem->checklistTemplate->item_name ?? '-' }}<br>
                                Next Action: {{ $ticket->pmCheckItem->next_action ?? '-' }}
                            </div>
                        </td>
                    </tr>
                    @endif
                    @if($ticket->attachment)
                    <tr>
                        <th class="bg-light">Foto Temuan</th>
                        <td>
                            <img src="{{ asset($ticket->attachment) }}" alt="Foto Temuan" class="img-thumbnail" style="max-height: 250px;">
                            <div class="mt-2"><a href="{{ asset($ticket->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Full Size</a></div>
                        </td>
                    </tr>
                    @endif
                    @if($ticket->after_photo)
                    <tr>
                        <th class="bg-light">Foto Selesai</th>
                        <td>
                            <img src="{{ asset($ticket->after_photo) }}" alt="Foto Selesai" class="img-thumbnail" style="max-height: 250px;">
                            <div class="mt-2"><a href="{{ asset($ticket->after_photo) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Full Size</a></div>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card mt-4 shadow-sm border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Aksi Pengerjaan</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-center gap-3 py-3">
                    @if(in_array($ticket->status, ['open', 'pending']))
                        <form action="{{ route('internal-tickets.startWork', $ticket) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-lg btn-success fw-bold px-4">
                                <i class="fas fa-play me-2"></i>Mulai Kerjakan
                            </button>
                        </form>
                    @endif

                    @if(in_array($ticket->status, ['open', 'onprogress', 'pending']))
                        <button type="button" class="btn btn-lg btn-warning fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalProgress">
                            <i class="fas fa-pen me-2"></i>Update Progress
                        </button>
                        <button type="button" class="btn btn-lg btn-danger fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalClose">
                            <i class="fas fa-check-circle me-2"></i>Tutup Tiket
                        </button>
                    @else
                        <div class="alert alert-success mb-0">Tiket internal ini sudah selesai.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mt-4 shadow-sm border-0">
            <div class="card-header bg-light fw-bold py-3"><i class="fas fa-comments me-2 text-primary"></i>Catatan Pengerjaan</div>
            <div class="card-body bg-white" style="max-height: 400px; overflow-y: auto;">
                @forelse($ticket->notes as $note)
                    @php $isOwnNote = $note->user_id && Auth::id() == $note->user_id; @endphp
                    <div class="mb-4 d-flex {{ $isOwnNote ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="p-3 rounded-3 shadow-sm {{ $isOwnNote ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 85%;">
                            <div class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                <strong>{{ $note->user->name ?? 'System' }}</strong><span>{{ $note->created_at->format('d/m H:i') }}</span>
                            </div>
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $note->note }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">Belum ada catatan pengerjaan.</div>
                @endforelse
            </div>
            @if($ticket->status !== 'closed')
            <div class="card-footer bg-white p-3 border-top">
                <form action="{{ route('internal-tickets.addNote', $ticket) }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <textarea name="note" class="form-control" placeholder="Update progres di sini..." rows="2" required></textarea>
                        <button class="btn btn-primary px-4" type="submit"><i class="fas fa-paper-plane fa-lg"></i></button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Ringkasan Waktu</h5></div>
            <div class="card-body">
                <p class="mb-2"><strong>Dibuat:</strong><br>{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                <p class="mb-2"><strong>Mulai:</strong><br>{{ $ticket->started_at ? $ticket->started_at->format('d/m/Y H:i') : '-' }}</p>
                <p class="mb-0"><strong>Selesai:</strong><br>{{ $ticket->closed_at ? $ticket->closed_at->format('d/m/Y H:i') : '-' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProgress" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('internal-tickets.updateProgress', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold">Update Progress</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="onprogress" {{ $ticket->status === 'onprogress' ? 'selected' : '' }}>On Progress</option>
                            <option value="pending" {{ $ticket->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Progress *</label>
                        <textarea name="note" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ringkasan Pengerjaan</label>
                        <textarea name="work_result" class="form-control" rows="3">{{ $ticket->work_result }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">PIC</label>
                            <input type="text" name="assigned_to_name" class="form-control" value="{{ $ticket->assigned_to_name }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Target Selesai</label>
                            <input type="date" name="target_date" class="form-control" value="{{ $ticket->target_date?->format('Y-m-d') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">Simpan Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalClose" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('internal-tickets.close', $ticket) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Tutup Tiket Internal</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hasil Pengerjaan *</label>
                        <textarea name="work_result" class="form-control" rows="5" required>{{ $ticket->work_result }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto Bukti Selesai</label>
                        <input type="file" name="after_photo" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold">Tutup Tiket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
