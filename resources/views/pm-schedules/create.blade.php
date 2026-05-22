@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold text-primary">Form Jadwal PM Baru</div>
            <form action="{{ route('pm.schedule.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Aset (Mesin) *</label>
                        <select class="form-select select2" name="asset_id" required>
                            {{-- Placeholder disederhanakan tanpa menyebut FA Code --}}
                            <option value="">-- Pilih Nama Mesin --</option>
                            @foreach($machines as $asset)
                                {{-- Hanya menampilkan Nama Aset --}}
                                <option value="{{ $asset->id }}">{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Jadwal *</label>
                        <select class="form-select" name="schedule_type" required>
                            @foreach($scheduleTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- INPUT BARU: PILIH PIC MAINTENANCE --}}
<div class="mb-3">
    <label class="form-label fw-bold">PIC (Penanggung Jawab MTC)</label>
    <select class="form-select select2" name="pic_name">
        <option value="">-- Pilih PIC (Opsional) --</option>
        @foreach($technicians as $tech)
            <option value="{{ $tech->name }}">{{ $tech->name }} ({{ $tech->department }})</option>
        @endforeach
    </select>
    <small class="text-muted">Teknisi yang bertanggung jawab rutin atas mesin ini kawan.</small>
</div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label text-dark" for="is_active">Status Aktif</label>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end">
                    <a href="{{ route('pm.schedule.index') }}" class="btn btn-secondary px-4">Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || '-- Pilih --';
        },
        allowClear: true
    });
});
</script>
@endpush
@endsection