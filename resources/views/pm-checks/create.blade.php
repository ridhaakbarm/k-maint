@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><h5 class="mb-0">Form Mulai Checklist PM</h5></div>
            @php
                $selectedDate = request('date', now()->toDateString());
                $selectedWeek = request('week', now()->weekOfYear);
            @endphp
            <form action="{{ route('pm-checks.store', $schedule->id) }}?{{ $schedule->schedule_type === 'daily' ? 'date='.$selectedDate : 'week='.$selectedWeek }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
    <label class="form-label fw-bold">Tanggal Checklist</label>
    {{-- Tampilan visual (format Indonesia) yang dikunci --}}
    <input type="text" class="form-control bg-light fw-bold text-secondary" 
           value="{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}" readonly tabindex="-1">
    
    {{-- Data asli (format database) yang dikirim ke controller --}}
    <input type="hidden" name="check_date" value="{{ $selectedDate }}">
    
    <small class="text-muted italic">*Tanggal otomatis tercatat hari ini</small>
</div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shift</label>
                            <select name="shift" class="form-select" required>
                                <option value="Shift 1">Shift 1 (Pagi)</option>
                                <option value="Shift 2">Shift 2 (Siang)</option>
                                <option value="Shift 3">Shift 3 (Malam)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Teknisi</label>
                        <input type="text" name="technician_name" class="form-control" value="{{ Auth::user()->name }}" required>
                    </div>
                    <div class="alert alert-info">
                        <strong>Aset:</strong> {{ $schedule->asset->name }}<br>
                        <strong>Tipe:</strong> {{ strtoupper($schedule->schedule_type) }}<br>
                        <strong>Jadwal:</strong> {{ str_replace('FA - ', '', $schedule->name) }}
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-play me-1"></i> Mulai Checklist</button>
                    <a href="{{ route('pm.execution.index', ['scheduleType' => $schedule->schedule_type]) }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
