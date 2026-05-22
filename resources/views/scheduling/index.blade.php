@extends('layouts.app')

@section('page_title', 'Penjadwalan PM')
@section('breadcrumb', 'Penjadwalan')

@section('content')

{{-- INFO BOX MINGGU INI --}}
<div class="row mb-2">
    <div class="col-12">
        <div class="callout callout-info">
            <h5><i class="fas fa-calendar-check"></i> Minggu Ini: Week {{ $currentWeek }}</h5>
            <p>Pilih tipe jadwal <strong>"Rutin (Mingguan)"</strong>. Sistem otomatis mendeteksi item checklist mana yang harus muncul berdasarkan tanggal deadline yang Anda pilih.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Form Penugasan Teknisi</h3>
            </div>
            <form action="{{ route('scheduling.generate') }}" method="POST">
                @csrf
                <div class="card-body">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>1. Pilih Tipe Jadwal</label>
                                <select name="schedule_type" id="schedule_type" class="form-control" required onchange="loadMachines()">
                                    <option value="">-- Pilih Tipe --</option>
                                    {{-- OPSI SUDAH DISEDERHANAKAN --}}
                                    <option value="weekly">Rutin (Mingguan & Bulanan)</option>
                                    <option value="yearly">Major (Tahunan)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="form-group">
                                <label>3. Tugaskan Kepada (Teknisi)</label>
                                <select name="technician_id" class="form-control" required>
                                    <option value="">-- Pilih Teknisi --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group bg-light p-3 border rounded">
                        <label>2. Pilih Mesin yang akan Dijadwalkan</label>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="checkAll" onchange="toggleAll(this)" disabled>
                                <label for="checkAll" class="custom-control-label font-weight-bold">Pilih Semua Mesin</label>
                            </div>
                            <span id="loading-indicator" style="display:none;" class="text-primary"><i class="fas fa-spinner fa-spin"></i> Loading...</span>
                        </div>
                        <hr class="mt-0">
                        
                        <div id="machine-list-container" style="max-height: 300px; overflow-y: auto;">
                            <p class="text-muted text-center mt-3">Silakan pilih tipe jadwal terlebih dahulu...</p>
                        </div>
                    </div>

                    {{-- Ganti bagian Form Batas Waktu dengan ini --}}
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>4. Target Minggu (Week Number)</label>
            <select name="target_week" class="form-control" required>
                @for ($i = 1; $i <= 52; $i++)
                    <option value="{{ $i }}" {{ $i == $currentWeek ? 'selected' : '' }}>
                        Week {{ $i }} {{ $i == $currentWeek ? '(Minggu Ini)' : '' }}
                    </option>
                @endfor
            </select>
            <small class="text-muted">Item checklist akan muncul berdasarkan Week yang dipilih.</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>5. Batas Waktu (Deadline)</label>
            <input type="date" name="due_date" class="form-control" required min="{{ date('Y-m-d') }}">
        </div>
    </div>
</div>

{{-- TAMBAHKAN TABEL MONITORING DI BAWAH FORM --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-tasks mr-1"></i> Daftar Penugasan Terjadwal</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 400px;">
                <table class="table table-head-fixed text-nowrap table-sm">
                    <thead>
                        <tr>
                            <th>Mesin</th>
                            <th>Teknisi</th>
                            <th class="text-center">Target</th>
                            <th>Deadline</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($existingSchedules as $check)
                        <tr>
                            <td><strong>{{ $check->pmSchedule->machine->code }}</strong></td>
                            <td>{{ $check->technician->name }}</td>
                            <td class="text-center"><span class="badge badge-info">Week {{ $check->week_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($check->due_date)->format('d M Y') }}</td>
                            <td>
                                <span class="badge badge-{{ $check->status == 'pending' ? 'warning' : 'success' }}">
                                    {{ strtoupper($check->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada penugasan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Distribusikan Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lightbulb mr-1"></i> Skenario Penggunaan</h3>
            </div>
            <div class="card-body">
                <p><strong>Contoh Kasus:</strong><br>Anda ingin membagi tugas PM Rutin minggu ini ke 2 teknisi.</p>
                <hr>
                <strong>Langkah 1:</strong>
                <ul>
                    <li>Pilih Tipe: <b>Rutin</b></li>
                    <li>Muncul semua mesin (misal 10 mesin).</li>
                    <li>Centang 5 mesin pertama (GL 1 - GL 5).</li>
                    <li>Pilih <b>Teknisi A</b>.</li>
                    <li>Klik Generate.</li>
                </ul>
                <strong>Langkah 2:</strong>
                <ul>
                    <li>Ulangi proses.</li>
                    <li>Centang 5 mesin sisanya (GL 6 - GL 10).</li>
                    <li>Pilih <b>Teknisi B</b>.</li>
                    <li>Klik Generate.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="icon fas fa-check"></i> {{ session('success') }}
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mt-3">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="icon fas fa-exclamation-triangle"></i> {{ session('warning') }}
    </div>
@endif
@endsection

@push('scripts')
<script>
function loadMachines() {
    const scheduleType = document.getElementById('schedule_type').value;
    const container = document.getElementById('machine-list-container');
    const checkAll = document.getElementById('checkAll');
    const loading = document.getElementById('loading-indicator');

    if (!scheduleType) {
        container.innerHTML = '<p class="text-muted text-center mt-3">Silakan pilih tipe jadwal terlebih dahulu...</p>';
        checkAll.disabled = true;
        checkAll.checked = false;
        return;
    }

    // UI Loading state
    container.innerHTML = '';
    loading.style.display = 'block';
    checkAll.disabled = true;

    // Panggil AJAX
    fetch(`{{ route('scheduling.get-machines') }}?schedule_type=${scheduleType}`)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';

            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-warning text-center">Tidak ada jadwal mesin aktif untuk kategori ini. <br><a href="{{ route("pm-schedules.create") }}">Buat Jadwal Baru</a></div>';
                return;
            }

            // Aktifkan checkbox "Pilih Semua"
            checkAll.disabled = false;
            checkAll.checked = false;

            // Loop data mesin dan buat checkbox
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'custom-control custom-checkbox mb-2 ml-3';
                // Kita pakai item.id (ID Jadwal) sebagai value, bukan ID Mesin
                div.innerHTML = `
                    <input class="custom-control-input machine-checkbox" type="checkbox" 
                           id="schedule_${item.id}" name="selected_schedules[]" value="${item.id}">
                    <label for="schedule_${item.id}" class="custom-control-label">
                        <strong>${item.machine.code}</strong> - ${item.machine.name}
                        <span class="badge badge-light ml-2">${item.name}</span>
                    </label>
                `;
                container.appendChild(div);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            loading.style.display = 'none';
            container.innerHTML = '<p class="text-danger text-center">Gagal memuat data. Silakan refresh.</p>';
        });
}

function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.machine-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}
</script>
@endpush