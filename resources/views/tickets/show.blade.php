@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    {{-- Banner info untuk tiket pending --}}
    @if($ticket->status == 'pending' && !(Auth::user()->isAdmin() || Auth::user()->isSPV()))
    <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-lock fa-2x me-3"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Tiket Dalam Status PENDING</h6>
                <p class="mb-0">Tiket ini sedang ditinjau oleh SPV. Anda hanya dapat melihat informasi, namun tidak dapat melakukan perubahan atau menambah catatan.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Detail Ticket: {{ $ticket->ticket_no }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Informasi Ticket</h5>
                    <div class="d-flex align-items-center gap-2">
                        @if(Auth::user()->id == 1 && Auth::user()->isAdmin())
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editTicketModal">
                                <i class="fas fa-edit me-1"></i> Edit Tiket
                            </button>
                        @endif
                        <span class="badge
    @if ($ticket->status == 'open') bg-danger
    @elseif($ticket->status == 'onprogress') bg-warning text-dark
    @elseif($ticket->status == 'pending') bg-dark {{-- Warna hitam untuk pending --}}
    @elseif($ticket->status == 'request_to_close') bg-info text-dark
    @elseif($ticket->status == 'rejected') bg-secondary
    @else bg-success @endif">
    {{ strtoupper(str_replace('_', ' ', $ticket->status)) }}
</span>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%" class="bg-light">Ticket No</th>
                            <td class="fw-bold text-primary">{{ $ticket->ticket_no }}</td>
                        </tr>

                        <tr>
                            <th class="bg-light">Mesin (Asset)</th>
                            <td>
                                @if ($ticket->asset)
                                    <span class="badge bg-secondary">{{ $ticket->asset->fa_code }}</span> - <strong>{{ $ticket->asset->name }}</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>

                        

                        <tr>
                            <th class="bg-light">Request Date</th>
                            <td>{{ $ticket->request_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Requester</th>
                            <td>{{ $ticket->requester->name }} ({{ $ticket->requester->department }})</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Subject</th>
                            <td><strong>{{ $ticket->subject }}</strong></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Description</th>
                            <td>{!! nl2br(e($ticket->description)) !!}</td>
                        </tr>
                        
                        @if ($ticket->ga_notes)
                            <tr>
                                <th class="bg-light">Maintenance Notes</th>
                                <td>{!! nl2br(e($ticket->ga_notes)) !!}</td>
                            </tr>
                        @endif
                        
                        @if ($ticket->attachment)
                            <tr>
                                <th class="bg-light">Foto Sebelum Perbaikan</th>
                                <td>
                                    <img src="{{ asset('/' . $ticket->attachment) }}" alt="Before Photo"
                                        class="img-thumbnail" style="max-height: 250px;">
                                    <div class="mt-2">
                                        <a href="{{ asset('/' . $ticket->attachment) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> Lihat Full Size
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                        
                        @if ($ticket->after_photo)
                            <tr>
                                <th class="bg-light">Foto Sesudah Perbaikan</th>
                                <td>
                                    <img src="{{ asset($ticket->after_photo) }}" alt="After Photo" class="img-thumbnail" style="max-height: 250px;">
                                    <div class="mt-2">
                                        <a href="{{ asset('attachments/' . $ticket->after_photo) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> Lihat Full Size
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        {{-- SERAH TERIMA --}}
                        @if($ticket->serah_terima_teknisi || $ticket->serah_terima_user)
                            <tr>
                                <th class="bg-light">Serah Terima</th>
                                <td>
                                    @if($ticket->serah_terima_teknisi)
                                        <div class="mb-1">
                                            <span class="badge bg-primary me-1">Teknisi:</span>
                                            <strong>{{ $ticket->serah_terima_teknisi }}</strong>
                                        </div>
                                    @endif
                                    @if($ticket->serah_terima_user)
                                        <div>
                                            <span class="badge bg-success me-1">Penerima:</span>
                                            <strong>{{ $ticket->serah_terima_user }}</strong>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        {{-- TANGGAL FOLLOW UP --}}
                        @if($ticket->estimated_date)
                            <tr>
                                <th class="bg-light">
                                    <i class="fas fa-calendar-check me-1 text-primary"></i>
                                    Tanggal Follow Up
                                </th>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold {{ \Carbon\Carbon::parse($ticket->estimated_date)->isPast() ? 'text-danger' : 'text-primary' }}">
                                            {{ \Carbon\Carbon::parse($ticket->estimated_date)->format('d/m/Y') }}
                                        </span>
                                        @if(\Carbon\Carbon::parse($ticket->estimated_date)->isPast())
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i> Lewat Tanggal
                                            </span>
                                        @else
                                            @php
                                                $daysUntil = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($ticket->estimated_date), false);
                                            @endphp
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-hourglass-half me-1"></i>
                                                {{ $daysUntil > 0 ? "{$daysUntil} hari lagi" : 'Hari ini' }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($ticket->pr_number)
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-file-invoice me-1"></i>
                                                No. PR: <strong>{{ $ticket->pr_number }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <th class="bg-light">Created At</th>
                            <td><small>{{ $ticket->created_at->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- CARD AKSI PENGERJAAN: Struktur Tunggal & Stabil --}}
<div class="card mt-4 shadow-sm border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Aksi Pengerjaan</h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-center gap-3 py-3">
            
            {{-- 1. STATUS OPEN ATAU SCHEDULE (Tiket baru atau yang sudah dijadwalkan) --}}
            @if(in_array($ticket->status, ['open', 'schedule']) && (Auth::user()->isMTC() || Auth::user()->isAdmin()))
                <form action="{{ route('tickets.startWork', $ticket) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-lg btn-success fw-bold px-4 shadow-sm">
                        <i class="fas fa-play me-2"></i>MULAI KERJAKAN SEKARANG
                    </button>
                </form>
                
                @if($ticket->status == 'open') {{-- Tombol jadwal hanya muncul jika masih open --}}
                <button type="button" class="btn btn-lg btn-info text-white px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalSchedule">
                    <i class="fas fa-calendar-alt me-2"></i>RE-SCHEDULE...
                </button>
                @endif

            {{-- 2. STATUS: ON PROGRESS --}}
            @elseif($ticket->status == 'onprogress' && (Auth::user()->isMTC() || Auth::user()->isAdmin()))
    @php
        // Cek apakah user ini sedang menjalankan timer untuk tiket ini kawan
        $isWorkingNow = \App\Models\TechnicianActivity::where('user_id', Auth::id())
                        ->where('reference_id', $ticket->id)
                        ->where('status', 'running')
                        ->exists();
    @endphp

    @if($isWorkingNow)
        {{-- Jika sedang dikerjakan user ini, munculkan Selesai & Pending --}}
        <button type="button" class="btn btn-lg btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFinish">
            <i class="fas fa-check-circle me-2"></i>PENGERJAAN SELESAI
        </button>
        <button type="button" class="btn btn-lg btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPending">
            <i class="fas fa-pause-circle me-2"></i>PENDING PENGERJAAN
        </button>
    @else
        {{-- Jika status ON PROGRESS tapi user ini tidak sedang "Running", munculkan RESUME --}}
        <form action="{{ route('tickets.resumeWork', $ticket) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-lg btn-info text-white fw-bold shadow-sm px-5">
                <i class="fas fa-play-circle me-2"></i>LANJUTKAN / RESUME
            </button>
        </form>
    @endif

            {{-- 3. STATUS: PENDING (Hanya Admin/SPV) --}}
            @elseif($ticket->status == 'pending' && (Auth::user()->isAdmin() || Auth::user()->isSPV()))
                <div class="alert alert-dark w-100 text-center mb-0 border-0 shadow-sm">
                    <h6 class="fw-bold mb-2"><i class="fas fa-user-shield me-2"></i>VERIFIKASI PENDING (Review SPV)</h6>
                    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalSpvReview">PROSES REVIEW SPV</button>
                </div>

            {{-- 4. STATUS: REQUEST TO CLOSE (Hanya Requester) --}}
            @elseif($ticket->status == 'request_to_close')
    @if(Auth::id() == $ticket->requester_id)
        <div class="d-flex gap-3">
            <form action="{{ route('tickets.closeTicket', $ticket) }}" method="POST" onsubmit="return confirm('Tutup tiket sekarang?')">
                @csrf
                <button type="submit" class="btn btn-lg btn-danger px-4 fw-bold shadow">
                    <i class="fas fa-lock me-2"></i>KONFIRMASI & TUTUP
                </button>
            </form>

            {{-- TOMBOL REJECT (Membuka Modal) --}}
            <button type="button" class="btn btn-lg btn-outline-warning px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalRejectByUser">
                <i class="fas fa-undo me-2"></i>TOLAK & REJECT
            </button>
        </div>
    @else
        <div class="alert alert-info w-100 mb-0">Menunggu konfirmasi pembuat tiket kawan.</div>
    @endif

            {{-- 5. JIKA STATUS LAIN ATAU AKSES TIDAK SESUAI --}}
            @else
                <div class="text-center py-2">
                    <p class="text-muted mb-0 italic">Tiket ini dalam status <strong>{{ strtoupper($ticket->status) }}</strong>.</p>
                    <p class="small text-muted">Tidak ada aksi tambahan yang tersedia untuk akun anda kawan.</p>
                </div>
            @endif {{-- DISINI HANYA BOLEH ADA SATU ENDIF UTAMA --}}

        </div>
    </div>
</div>

            {{-- SISTEM OBROLAN / NOTES --}}
            <div class="card mt-4 shadow-sm border-0">
                <div class="card-header bg-light fw-bold py-3"><i class="fas fa-comments me-2 text-primary"></i>CATATAN PENGERJAAN & OBROLAN</div>
                <div class="card-body bg-white" style="max-height: 400px; overflow-y: auto; background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
                    <div class="chat-wrapper">
                        @forelse($ticket->notes as $note)
                            @php $isOwnNote = $note->user_id && Auth::id() == $note->user_id; @endphp
                            <div class="mb-4 d-flex {{ $isOwnNote ? 'justify-content-end' : 'justify-content-start' }}">
                                <div class="p-3 rounded-3 shadow-sm {{ $isOwnNote ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 85%;">
                                    <div class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                        <strong>{{ $note->user->name ?? 'System' }}</strong><span>{{ $note->created_at->format('d/m H:i') }}</span>
                                    </div>
                                    <p class="mb-0 fs-5" style="white-space: pre-wrap;">{{ $note->note }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted italic">Belum ada catatan pengerjaan kawan.</div>
                        @endforelse
                    </div>
                </div>
                {{-- Batasi akses tambah note: tidak boleh jika status PENDING dan bukan SPV/Admin --}}
                @if(!($ticket->status == 'pending' && !(Auth::user()->isAdmin() || Auth::user()->isSPV())))
                <div class="card-footer bg-white p-3 border-top">
                    <form action="{{ route('tickets.addNote', $ticket) }}" method="POST">
                        @csrf
                        <div class="input-group"><textarea name="note" class="form-control" placeholder="Update progres di sini..." rows="2" required></textarea><button class="btn btn-primary px-4" type="submit"><i class="fas fa-paper-plane fa-lg"></i></button></div>
                    </form>
                </div>
                @else
                <div class="card-footer bg-light p-3 border-top">
                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-lock me-1"></i> Tiket dalam status <strong>PENDING</strong>. Hanya SPV/Admin yang dapat menambah catatan.
                    </div>
                </div>
                @endif
            </div>

            {{-- TIMELINE STATUS --}}
            
        </div>

        <div class="col-md-4">
            {{-- SIDEBAR --}}
            <div class="card mt-1 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Riwayat Perjalanan Tiket</h5></div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach ($ticket->statusHistories as $history)
                            <div class="timeline-item">
                                <div class="timeline-marker {{ $history->getStatusBadgeClass($history->new_status) }}"></div>
                                <div class="timeline-content">
                                    <h6 class="fw-bold">{{ $history->getActionDescription() }}</h6>
                                    <p class="text-muted mb-1 small"><i class="fas fa-user me-1"></i>{{ $history->user->name ?? 'System' }} - {{ $history->created_at->format('d/m/Y H:i') }}</p>
                                    <span class="badge {{ $history->getStatusBadgeClass($history->new_status) }}">{{ strtoupper($history->new_status) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            
        </div>
    </div>

    {{-- TEMPLATE VENDOR --}}
    <template id="vendor_template">
        <div class="vendor-group p-3 border rounded mb-3 mt-3 bg-white shadow-sm">
            <h6 class="mb-3 vendor-title">Vendor Baru</h6>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label small">Status Vendor</label>
                    <select class="form-select form-select-sm" name="vendor_status[]">
                        <option value="onprogress">On Progress</option>
                        <option value="schedule">Schedule</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Nama Vendor</label>
                    <select class="form-control vendor-select2" name="vendor_name[]" style="width: 100%;">
                        <option value="">-- Cari Vendor --</option>
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-vendor-btn mt-2">Hapus Vendor</button>
        </div>
       
    </template>
 {{-- MODAL JADWALKAN --}}
<div class="modal fade" id="modalSchedule" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('tickets.startWork', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">Jadwalkan Pengerjaan</h5>
                </div>
                <div class="modal-body py-4">
                    <label class="form-label fw-bold">Pilih Tanggal Rencana Kerja:</label>
                    <input type="date" name="planned_date" class="form-control form-control-lg" min="{{ date('Y-m-d') }}" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL SELESAI --}}
<div class="modal fade" id="modalFinish" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('tickets.markAsFinished', $ticket) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Selesaikan Pekerjaan</h5>
                </div>
                <div class="modal-body py-4">
                    {{-- 1. Input Foto (Opsional) --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto Bukti Selesai (Opsional):</label>
                        <input type="file" name="after_photo" class="form-control" accept="image/*" capture="environment">
                        <small class="text-muted">Foto tidak wajib, tapi disarankan untuk dokumentasi.</small>
                    </div>

                    {{-- 2. INPUT BARU: PENYEBAB MASALAH --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Penyebab Masalah:</label>
                        <textarea name="problem_cause" class="form-control" rows="2" placeholder="Jelaskan kenapa mesin ini rusak kawan..." required></textarea>
                    </div>

                    {{-- 3. Catatan Akhir --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Penyelesaian:</label>
                        <textarea name="closing_note" class="form-control" rows="3" placeholder="Apa saja yang sudah diperbaiki kawan?"></textarea>
                    </div>

                    {{-- 4. SERAH TERIMA --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-primary">Nama Teknisi (Serah Terima):</label>
                            <input type="text" name="serah_terima_teknisi" class="form-control"
                                   placeholder="Nama teknisi yang mengerjakan" value="{{ Auth::user()->name }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-success">Nama User Penerima:</label>
                            <input type="text" name="serah_terima_user" class="form-control"
                                   placeholder="Nama user yang menerima hasil">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Belum Selesai</button>
                    <button type="submit" class="btn btn-success fw-bold">Kirim ke Requester</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PENDING (Teknisi) --}}
<div class="modal fade" id="modalPending" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('tickets.setPending', $ticket) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning"><h5 class="modal-title fw-bold">Pending Pengerjaan</h5></div>
                <div class="modal-body">
                    <label class="fw-bold">Alasan Pending (Sparepart/Alat/Lainnya):</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning fw-bold">Simpan Pending</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL SPV REVIEW --}}
<div class="modal fade" id="modalSpvReview" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('tickets.spvReview', $ticket) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold">Verifikasi SPV</h5></div>
                <div class="modal-body">
                    <label class="fw-bold">Pilih Tindakan:</label>
                    <select name="action" class="form-select mb-3" id="spvAction" onchange="toggleSpvInput(this.value)">
                        <option value="re-work">Lanjutkan (Berikan Instruksi)</option>
                        <option value="menunggu-sparepart">Menunggu Sparepart</option>
                        <option value="koordinasi-produksi">Koordinasi dengan Produksi</option>
                        <option value="perbaikan-eksternal">Perbaikan dilakukan Pihak Eksternal</option>
                    </select>

                    {{-- Bagian Instruksi (untuk re-work) --}}
                    <div id="div-note" style="display:block;">
                        <label class="fw-bold">Instruksi Pengerjaan:</label>
                        <textarea name="spv_note" class="form-control" rows="3" placeholder="Berikan arahan untuk teknisi..."></textarea>
                    </div>

                    {{-- Bagian Menunggu Sparepart --}}
                    <div id="div-sparepart" style="display:none;">
                        <div class="mb-3">
                            <label class="fw-bold text-primary"><i class="fas fa-file-invoice me-1"></i> Nomor PR (Purchase Requisition):</label>
                            <input type="text" name="pr_number" class="form-control" placeholder="Contoh: PR/2026/001">
                        </div>

                        <div class="mb-0">
                            <label class="fw-bold"><i class="fas fa-calendar-check me-1"></i> Estimasi Waktu Pengerjaan:</label>
                            <input type="date" name="estimated_date" class="form-control">
                            <small class="text-muted">Tanggal untuk follow-up kelanjutan tiket ini</small>
                        </div>
                    </div>

                    {{-- Bagian Koordinasi dengan Produksi --}}
                    <div id="div-koordinasi" style="display:none;">
                        <div class="mb-3">
                            <label class="fw-bold text-warning"><i class="fas fa-industry me-1"></i> Catatan Koordinasi:</label>
                            <textarea name="coordination_notes" class="form-control" rows="3" placeholder="Jelaskan hal yang perlu dikoordinasikan dengan produksi..."></textarea>
                        </div>

                        <div class="mb-0">
                            <label class="fw-bold"><i class="fas fa-calendar-check me-1"></i> Estimasi Waktu Pengerjaan:</label>
                            <input type="date" name="estimated_date" class="form-control">
                            <small class="text-muted">Perkiraan waktu selesai koordinasi</small>
                        </div>
                    </div>

                    {{-- Bagian Perbaikan Eksternal --}}
                    <div id="div-eksternal" style="display:none;">
                        <div class="mb-3">
                            <label class="fw-bold text-danger"><i class="fas fa-user-tie me-1"></i> Nama Pihak Eksternal:</label>
                            <input type="text" name="external_vendor" class="form-control" placeholder="Nama vendor/pihak eksternal">
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold"><i class="fas fa-sticky-note me-1"></i> Catatan:</label>
                            <textarea name="external_notes" class="form-control" rows="3" placeholder="Detail perbaikan yang dilakukan pihak eksternal..."></textarea>
                        </div>

                        <div class="mb-0">
                            <label class="fw-bold"><i class="fas fa-calendar-check me-1"></i> Estimasi Waktu Pengerjaan:</label>
                            <input type="date" name="estimated_date" class="form-control">
                            <small class="text-muted">Perkiraan waktu selesai perbaikan eksternal</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary fw-bold">Kirim Keputusan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL REJECT TIKET --}}
<div class="modal fade" id="modalRejectByUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('tickets.rejectByUser', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold">Alasan Penolakan</h5>
                </div>
                <div class="modal-body py-4">
                    <label class="fw-bold mb-2">Kenapa perbaikannya ditolak kawan?</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Contoh: Mesin masih bunyi kasar..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning fw-bold">Kirim Balik ke Teknisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <style>
        .timeline { position: relative; padding-left: 30px; }
        .timeline-item { position: relative; margin-bottom: 25px; }
        .timeline-marker { position: absolute; left: -30px; top: 0; width: 18px; height: 18px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #ddd; }
        .timeline-content { padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; }
        .timeline-item:not(:last-child) .timeline-content { border-left: 2px solid #eee; padding-left: 20px; margin-left: -21px; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi modal SPV Review: pastikan state awal sudah benar saat modal dibuka
            const modalSpvEl = document.getElementById('modalSpvReview');
            if (modalSpvEl) {
                modalSpvEl.addEventListener('show.bs.modal', function () {
                    const select = document.getElementById('spvAction');
                    if (select) toggleSpvInput(select.value);
                });
            }

            const internalCheckbox = document.getElementById('internal_checkbox');
            const mtcCheckbox = document.getElementById('mtc_checkbox');
            const externalCheckbox = document.getElementById('external_checkbox');

            const internalOptionsDiv = document.getElementById('internal_options');
            const mtcInputDiv = document.getElementById('mtc_input');
            const externalOptionsDiv = document.getElementById('external_options');

            if (internalCheckbox) {
                internalCheckbox.addEventListener('change', () => {
                    internalOptionsDiv.style.display = internalCheckbox.checked ? 'block' : 'none';
                });
            }
            if (mtcCheckbox) {
                mtcCheckbox.addEventListener('change', () => {
                    mtcInputDiv.style.display = mtcCheckbox.checked ? 'block' : 'none';
                });
            }
            if (externalCheckbox) {
                externalCheckbox.addEventListener('change', () => {
                    externalOptionsDiv.style.display = externalCheckbox.checked ? 'block' : 'none';
                });
            }

            const addVendorBtn = document.getElementById('add_vendor_btn');
            const vendorContainer = document.getElementById('vendor_container');

            if (addVendorBtn) {
                addVendorBtn.addEventListener('click', function() {
                    const template = document.getElementById('vendor_template');
                    const clone = template.content.cloneNode(true);
                    vendorContainer.appendChild(clone);
                    initVendorSelect2();
                });
            }

            $(document).on('click', '.remove-vendor-btn', function() {
                $(this).closest('.vendor-group').remove();
            });
        });
        function toggleSpvInput(val) {
    // Semua div dan input-nya
    const allDivs = ['div-note', 'div-sparepart', 'div-koordinasi', 'div-eksternal'];

    // Sembunyikan & disable semua div agar input-nya tidak ikut terkirim ke server
    allDivs.forEach(function(id) {
        const div = document.getElementById(id);
        if (!div) return;
        div.style.display = 'none';
        div.querySelectorAll('input, textarea, select').forEach(function(el) {
            el.disabled = true;
        });
    });

    // Tentukan div yang aktif sesuai pilihan
    const activeMap = {
        're-work': 'div-note',
        'menunggu-sparepart': 'div-sparepart',
        'koordinasi-produksi': 'div-koordinasi',
        'perbaikan-eksternal': 'div-eksternal'
    };

    const activeId = activeMap[val];
    if (activeId) {
        const activeDiv = document.getElementById(activeId);
        if (activeDiv) {
            activeDiv.style.display = 'block';
            // Enable semua input di div yang aktif agar terkirim ke server
            activeDiv.querySelectorAll('input, textarea, select').forEach(function(el) {
                el.disabled = false;
            });
        }
    }
}
    </script>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            function initVendorSelect2() {
                $('.vendor-select2').select2({
                    placeholder: '-- Cari Vendor --',
                    ajax: {
                        url: "{{ route('search.vendor') }}",
                        dataType: 'json',
                        processResults: data => ({ results: data.map(i => ({ id: i.nama_vendor, text: i.nama_vendor })) })
                    }
                });
            }

            $(document).ready(function() {
                initVendorSelect2();
                
                // INTI PERUBAHAN: Dropdown PIC Maintenance
                $('#mtc_pic_name').select2({
                    placeholder: 'Cari Petugas Maintenance...',
                    allowClear: true,
                    ajax: {
                        url: '{{ route("search.pic") }}',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: data => ({
                            results: data.map(item => ({
                                id: item.name,
                                text: item.name + ' - ' + item.department
                            }))
                        })
                    }
                });
            });
        </script>
    @endpush

    {{-- Modal Edit Tiket (Hanya untuk Admin ID = 1) --}}
    @if(Auth::user()->id == 1 && Auth::user()->isAdmin())
    <div class="modal fade" id="editTicketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Tiket: {{ $ticket->ticket_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('tickets.updateBasic', $ticket->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Mesin (Asset) <span class="text-danger">*</span></label>
                                <select name="asset_id" class="form-select select2" required>
                                    <option value="">-- Pilih Mesin --</option>
                                    @foreach($assets ?? \App\Models\Asset::orderBy('name')->get() as $asset)
                                        <option value="{{ $asset->id }}" {{ $ticket->asset_id == $asset->id ? 'selected' : '' }}>
                                            {{ $asset->fa_code }} - {{ $asset->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject', $ticket->subject) }}" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Deskripsi <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="5" required>{{ old('description', $ticket->description) }}</textarea>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Info:</strong> Perubahan hanya akan mengupdate Mesin, Subject, dan Deskripsi tiket.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection
