@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

@php
    $pmAsset = $pmCheckItem?->pmCheck?->pmSchedule?->asset;
    $defaultSubject = $pmCheckItem
        ? 'Follow up PM - ' . ($pmCheckItem->checklistTemplate->item_name ?? 'Item PM')
        : '';
    $defaultDescription = $pmCheckItem
        ? "Temuan dari PM:\nItem: " . ($pmCheckItem->checklistTemplate->item_name ?? '-') .
          "\nKondisi: " . ($pmCheckItem->condition ?? '-') .
          "\nAction Taken: " . ($pmCheckItem->action_taken ?? '-') .
          "\nTindakan Selanjutnya: " . ($pmCheckItem->next_action ?? '-')
        : '';
@endphp

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-0">Buat Tiket Internal</h1>
        <small class="text-muted">Untuk temuan PM atau instruksi lisan yang perlu dicatat pengerjaannya.</small>
    </div>
    <a href="{{ route('internal-tickets.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum lengkap.</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-clipboard-check me-2"></i>Form Tiket Internal</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('internal-tickets.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="pm_check_item_id" value="{{ old('pm_check_item_id', $pmCheckItem->id ?? '') }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold">No Tiket</label>
                        <input type="text" class="form-control bg-light fw-bold text-primary" name="ticket_no" value="{{ old('ticket_no', $generatedTicketNo) }}" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Sumber Tiket *</label>
                            <select class="form-select" name="source_type" required>
                                <option value="pm" {{ old('source_type', $pmCheckItem ? 'pm' : 'lisan') === 'pm' ? 'selected' : '' }}>Temuan PM</option>
                                <option value="lisan" {{ old('source_type', $pmCheckItem ? 'pm' : 'lisan') === 'lisan' ? 'selected' : '' }}>Instruksi Lisan Leader</option>
                                <option value="temuan_lain" {{ old('source_type') === 'temuan_lain' ? 'selected' : '' }}>Temuan Lain</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Prioritas *</label>
                            <select class="form-select" name="priority" required>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Asset / Mesin</label>
                        <select class="form-select" id="asset_id" name="asset_id">
                            <option value="">-- Pilih Mesin --</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" {{ (string) old('asset_id', $pmAsset->id ?? '') === (string) $asset->id ? 'selected' : '' }}>
                                    {{ $asset->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject *</label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject', $defaultSubject) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi / Temuan *</label>
                        <textarea name="description" class="form-control" rows="7" required>{{ old('description', $defaultDescription) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">PIC / Dikerjakan Oleh</label>
                            <input type="text" name="assigned_to_name" class="form-control" value="{{ old('assigned_to_name') }}" placeholder="Nama teknisi / tim">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Target Selesai</label>
                            <input type="date" name="target_date" class="form-control" value="{{ old('target_date') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Attachment Temuan</label>
                        <input type="file" name="attachment" class="form-control" accept="image/*">
                        <small class="text-muted">Opsional. Format gambar maksimal 10MB.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-paper-plane"></i> Submit Tiket Internal
                        </button>
                        <a href="{{ route('internal-tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Konteks</h5>
            </div>
            <div class="card-body">
                @if($pmCheckItem)
                    <div class="alert alert-info mb-0">
                        <h6 class="fw-bold"><i class="fas fa-calendar-check me-1"></i>Temuan PM Terhubung</h6>
                        <p class="small mb-1"><strong>Mesin:</strong> {{ $pmAsset->name ?? '-' }}</p>
                        <p class="small mb-1"><strong>Item:</strong> {{ $pmCheckItem->checklistTemplate->item_name ?? '-' }}</p>
                        <p class="small mb-0"><strong>Next Action:</strong> {{ $pmCheckItem->next_action ?? '-' }}</p>
                    </div>
                @else
                    <div class="alert alert-secondary mb-0">
                        <h6 class="fw-bold"><i class="fas fa-info-circle me-1"></i>Catatan</h6>
                        <p class="small mb-0">Gunakan sumber "Instruksi Lisan Leader" untuk pekerjaan yang selama ini belum punya rekam jejak formal.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#asset_id').select2({
        placeholder: '-- Pilih Mesin --',
        allowClear: true
    });
});
</script>
@endpush
