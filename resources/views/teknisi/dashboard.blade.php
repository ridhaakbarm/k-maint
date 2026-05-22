@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h3 class="fw-bold mb-4">Panel Kerja Teknisi</h3>

    {{-- JIKA ADA PEKERJAAN AKTIF --}}
    @if($currentActivity)
        <div class="card border-0 shadow-sm bg-success text-white mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-1 opacity-75 text-uppercase">Tugas Sedang Berjalan:</h5>
                        <h2 class="fw-bold mb-1">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            {{ $currentActivity->description }}
                        </h2>
                        <p class="mb-0">
                            <i class="far fa-clock me-1"></i> Mulai sejak: <strong>{{ $currentActivity->start_time->format('H:i') }}</strong>
                        </p>
                    </div>
                    
                    {{-- TOMBOL SELESAI --}}
                    <div class="mt-3 mt-md-0">
                        <form action="{{ route('monitoring.stop', $currentActivity->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-light btn-lg px-5 fw-bold text-success shadow">
                                <i class="fas fa-stop-circle me-2"></i> SELESAI KERJA
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- TAMPILAN JIKA TIDAK ADA PEKERJAAN (FORM INPUT SEPERTI SEBELUMNYA) --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-primary text-white fw-bold">Pilih Aktivitas Baru</div>
            <div class="card-body">
                <form action="{{ route('monitoring.start') }}" method="POST">
                    @csrf
                    <input type="hidden" name="category" value="Lain-lain">
                    <div class="row g-2">
                        <div class="col-md-9">
                            <select name="description" class="form-select form-select-lg" required id="daily_select" onchange="toggleManual(this)">
                                <option value="">-- Mau mengerjakan apa? --</option>
                                <option value="Istirahat">Istirahat</option>
                                <option value="Cek Utility">Cek Utility</option>
                                <option value="Briefing">Briefing</option>
                                <option value="Meeting">Meeting</option>
                                <option value="Training">Training</option>
                                <option value="OTHER_VAL">Tulis Manual...</option>
                            </select>
                            <input type="text" name="other_desc" id="manual_input" class="form-control mt-2 d-none" placeholder="Ketik kegiatan manual...">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">MULAI KERJA</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        @php
            // Cari attendance yang aktif (belum clock-out), cek hari ini atau kemarin (untuk shift 3 cross-day)
            $att = \App\Models\TechnicianAttendance::where('user_id', Auth::id())
                ->where(function($query) {
                    $query->where('date', now()->toDateString())
                          ->orWhere(function($q) {
                              // Untuk shift 3, cek kemarin jika belum clock-out
                              $q->where('date', now()->subDay()->toDateString())
                                ->whereNull('clock_out');
                          });
                })
                ->orderBy('created_at', 'desc')
                ->first();
        @endphp

        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="fw-bold mb-1">Absensi Harian</h5>
                <p class="small text-muted mb-0">Input jam kerja untuk menghitung workload anda hari ini.</p>
            </div>
            <div class="col-md-6 text-end">
                @if(!$att)
                    <form action="{{ route('monitoring.clock-in') }}" method="POST" class="d-inline" id="clockInForm">
                        @csrf
                        <select name="shift" class="btn btn-outline-primary btn-sm me-2" required id="shiftSelect">
                            <option value="Shift 1">Shift 1</option>
                            <option value="Shift 2">Shift 2</option>
                            <option value="Shift 3">Shift 3</option>
                        </select>
                        <button type="submit" class="btn btn-primary" onclick="return confirmClockIn()">CLOCK IN</button>
                    </form>
                @elseif(!$att->clock_out)
                    <span class="badge bg-success me-3">
                        IN: {{ $att->clock_in->format('H:i') }}
                        @if($att->date != now()->toDateString())
                            <small class="text-white-50">({{ $att->clock_in->format('d M') }})</small>
                        @endif
                    </span>
                    <form action="{{ route('monitoring.clock-out') }}" method="POST" class="d-inline" id="clockOutForm">
                        @csrf
                        <button type="submit" class="btn btn-danger" onclick="return confirmClockOut()">CLOCK OUT</button>
                    </form>
                @else
                    <span class="badge bg-dark">WORK COMPLETED ({{ $att->clock_in->format('H:i') }} - {{ $att->clock_out->format('H:i') }})</span>
                @endif
            </div>
        </div>
    </div>
</div>
    @endif
</div>

<script>
function toggleManual(select) {
    const manual = document.getElementById('manual_input');
    manual.classList.toggle('d-none', select.value !== 'OTHER_VAL');
    manual.required = (select.value === 'OTHER_VAL');
}

// Konfirmasi Clock In dengan Modal
function confirmClockIn() {
    const shift = document.getElementById('shiftSelect').value;
    document.getElementById('modalShiftText').textContent = shift;

    // Tampilkan modal
    const clockInModal = new bootstrap.Modal(document.getElementById('clockInModal'));
    clockInModal.show();

    // Handle confirm button
    document.getElementById('confirmClockInBtn').onclick = function() {
        clockInModal.hide();
        document.getElementById('clockInForm').submit();
    };

    return false;
}

// Konfirmasi Clock Out dengan Modal
function confirmClockOut() {
    @php
        // Hitung durasi kerja dari clock_in sampai sekarang
        $clockInTime = isset($att) && $att->clock_in ? $att->clock_in : null;
        $workDurationText = '-';

        if ($clockInTime) {
            $diff = $clockInTime->diff(now());
            $hours = $diff->h + ($diff->days * 24);
            $minutes = $diff->i;
            $workDurationText = "{$hours} jam {$minutes} menit";
        }
    @endphp

    const workDuration = "{{ $workDurationText }}";
    document.getElementById('modalWorkDuration').textContent = workDuration;

    // Tampilkan modal
    const clockOutModal = new bootstrap.Modal(document.getElementById('clockOutModal'));
    clockOutModal.show();

    // Handle confirm button
    document.getElementById('confirmClockOutBtn').onclick = function() {
        clockOutModal.hide();
        document.getElementById('clockOutForm').submit();
    };

    return false;
}
</script>
{{-- Modal Clock In --}}
<div class="modal fade" id="clockInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i>Konfirmasi Clock In</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-user-clock fa-4x text-primary mb-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Hallo, {{ auth()->user()->name }}!</h5>
                <p class="text-muted mb-3">Apakah kamu yakin Clock In sekarang?</p>
                <div class="alert alert-info">
                    <i class="fas fa-calendar-week me-2"></i>
                    <strong>Shift:</strong> <span id="modalShiftText">-</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="confirmClockInBtn">
                    <i class="fas fa-check me-1"></i> Ya, Clock In Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Clock Out --}}
<div class="modal fade" id="clockOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-sign-out-alt me-2"></i>Konfirmasi Clock Out</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-user-clock fa-4x text-danger mb-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Hallo, {{ auth()->user()->name }}!</h5>
                <p class="text-muted mb-3">Apakah kamu yakin Clock Out sekarang?</p>
                <div class="alert alert-success">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Durasi Kerja:</strong> <span id="modalWorkDuration">-</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmClockOutBtn">
                    <i class="fas fa-check me-1"></i> Ya, Clock Out Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

@endsection