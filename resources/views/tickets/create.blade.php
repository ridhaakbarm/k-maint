@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Buat Ticket Baru</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

{{-- CEK JUMLAH TIKET REQUEST_TO_CLOSE --}}
@php
    $requestToCloseCount = \App\Models\Ticket::where('requester_id', Auth::id())
        ->where('status', 'request_to_close')
        ->count();
    
    $canCreateTicket = $requestToCloseCount < 5;
    $requestToCloseTickets = \App\Models\Ticket::where('requester_id', Auth::id())
        ->where('status', 'request_to_close')
        ->get();
@endphp

{{-- TAMPILKAN ALERT JIKA TIKET REQUEST_TO_CLOSE MENDEKATI BATAS --}}
@if(!$canCreateTicket)
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-ban me-2 fa-lg"></i>
    <strong>Tidak Dapat Membuat Tiket Baru!</strong>
    <hr>
    <p class="mb-0">Anda sudah memiliki <strong>{{ $requestToCloseCount }} tiket</strong> dengan status <strong>Request to Close</strong> (maksimal 5 tiket).</p>
    <p class="small mt-2 mb-0">Silahkan selesaikan (close) beberapa tiket terlebih dahulu:</p>
    <ul class="small mt-1">
        @foreach($requestToCloseTickets as $ticket)
            <li>
                <a href="{{ route('tickets.show', $ticket) }}" class="alert-link">
                    {{ $ticket->ticket_no }} - {{ Str::limit($ticket->subject, 40) }}
                </a>
            </li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@elseif($requestToCloseCount >= 3)
<div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Perhatian!</strong> Anda sudah memiliki <strong>{{ $requestToCloseCount }} tiket</strong> dengan status Request to Close.
    Maksimal 5 tiket. Segera selesaikan tiket-tiket tersebut.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header {{ !$canCreateTicket ? 'bg-secondary' : 'bg-primary' }} text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Form Ticket Baru
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="ticket_no" class="form-label fw-bold">Ticket No</label>
                        <input type="text" class="form-control bg-light fw-bold text-primary" id="ticket_no" name="ticket_no" 
                            value="{{ $generatedTicketNo }}" readonly {{ !$canCreateTicket ? 'disabled' : '' }}>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label for="asset_id" class="form-label fw-bold">Pilih Asset (Mesin) *</label>
                            <select class="form-select" id="asset_id" name="asset_id" required {{ !$canCreateTicket ? 'disabled' : '' }}>
                                <option value="">-- Pilih Mesin --</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                        {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label fw-bold">Subject *</label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject"
                            name="subject" value="{{ old('subject') }}" placeholder="Masukkan subject ticket..." required {{ !$canCreateTicket ? 'disabled' : '' }}>
                        @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Description *</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            rows="5" placeholder="Jelaskan detail kendala pada bagian mesin tersebut..." required {{ !$canCreateTicket ? 'disabled' : '' }}>{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="attachment" class="form-label fw-bold">Attachment (Gambar)</label>
                        <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                            id="attachment" name="attachment" accept="image/*" {{ !$canCreateTicket ? 'disabled' : '' }}>
                        <div class="form-text text-muted">Format: JPG, PNG, GIF (Maksimal 10MB)</div>
                    </div>

                    <div class="d-flex gap-2">
                        @if(!$canCreateTicket)
                            <button type="button" class="btn btn-secondary px-4" disabled>
                                <i class="fas fa-ban"></i> Tidak Dapat Submit
                            </button>
                            <a href="{{ route('tickets.index') }}" class="btn btn-warning">
                                <i class="fas fa-eye"></i> Selesaikan Tiket Terlebih Dahulu
                            </a>
                        @else
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-paper-plane"></i> Submit Ticket
                            </button>
                        @endif
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Informasi</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Petunjuk:</h6>
                    <ul class="mb-0 ps-3 small">
                        <li>Nomor tiket di-*generate* secara otomatis oleh sistem.</li>
                        <li>Pilih Mesin terlebih dahulu agar daftar **Bagian Mesin** muncul.</li>
                        <li>Isi deskripsi dengan detail agar tim teknis mudah mengidentifikasi masalah.</li>
                    </ul>
                </div>
                
                {{-- INFORMASI BATAS TIKET --}}
                <div class="alert {{ $requestToCloseCount >= 5 ? 'alert-danger' : ($requestToCloseCount >= 3 ? 'alert-warning' : 'alert-secondary') }} mt-3">
                    <h6><i class="fas fa-chart-line"></i> Status Tiket Anda:</h6>
                    <p class="small mb-0">
                        <strong>Tiket Request to Close:</strong> {{ $requestToCloseCount }} / 5<br>
                        @if($requestToCloseCount < 5)
                            <span class="text-success">
                                <i class="fas fa-check-circle"></i> Anda masih bisa membuat {{ 5 - $requestToCloseCount }} tiket baru
                            </span>
                        @else
                            <span class="text-danger">
                                <i class="fas fa-ban"></i> Batas maksimal tercapai! Tidak bisa membuat tiket baru.
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const canCreateTicket = {{ $canCreateTicket ? 'true' : 'false' }};
    
    if (canCreateTicket) {
        // Inisialisasi Select2
        $('#asset_id').select2({
            placeholder: '-- Pilih Mesin --',
            allowClear: true
        });
    }
});
</script>
@endpush
@endsection