@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 fw-bold text-dark">Laporan Follow Up Temuan PM</h1>
    <span class="badge bg-danger">Monitoring Temuan PM</span>
</div>

{{-- TABEL 1: ON PROGRESS (BELUM KELAR) --}}
<h4 class="fw-bold text-primary mb-3"><i class="fas fa-tools me-2"></i>On Progress / Menunggu Follow Up</h4>
<div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-followup" style="font-size: 0.85rem;">
                <thead class="bg-light text-uppercase">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Detail</th>
                        <th>Tanggal Cek</th>
                        <th>Mesin</th>
                        <th>Item & Temuan</th>
                        <th>Tindakan (Action)</th>
                        <th>Tindakan Selanjutnya</th>
                        <th>Follow Up Note</th>
                        <th>Tgl Kerja</th>
                        <th>Oleh</th>
                        <th>Verificator</th>
                        <th>Approval</th>
                        <th>Bukti</th>
                        <th style="min-width: 120px;">Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ongoingItems as $item)
                        @include('reports.pm.partials._table_row', ['item' => $item])
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- TABEL 2: HISTORICAL (SUDAH OK) --}}
<h4 class="fw-bold text-success mb-3"><i class="fas fa-check-circle me-2"></i>Historical (Selesai)</h4>
<div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-followup" style="font-size: 0.85rem;">
                <thead class="bg-light text-uppercase">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Detail</th>
                        <th>Tanggal Cek</th>
                        <th>Mesin</th>
                        <th>Item & Temuan</th>
                        <th>Tindakan (Action)</th>
                        <th>Tindakan Selanjutnya</th>
                        <th>Follow Up Note</th>
                        <th>Tgl Kerja</th>
                        <th>Oleh</th>
                        <th>Verificator</th>
                        <th>Approval</th>
                        <th>Bukti</th>
                        <th style="min-width: 120px;">Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historyItems as $item)
                        @include('reports.pm.partials._table_row', ['item' => $item])
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL FOLLOW UP LENGKAP --}}
<div class="modal fade" id="modalUploadPhoto" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <form id="formUploadPhoto" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Monitoring & Update Temuan PM</h5>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Catatan Follow Up (Tindakan):</label>
                            <textarea name="follow_up_note" class="form-control" rows="2" placeholder="Input progres di sini..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Pengerjaan:</label>
                            <input type="date" name="execution_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dikerjakan Oleh:</label>
                            <input type="text" name="executed_by" class="form-control" placeholder="Nama teknisi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Verificator (MTC/SPV):</label>
                            <input type="text" name="verified_by" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Approval (Produksi/User):</label>
                            <input type="text" name="approved_by" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Foto Bukti:</label>
                            <input type="file" name="photo_after" class="form-control" accept="image/*">
                            <small class="text-muted italic">*Kosongkan jika tidak ingin mengganti foto bukti.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Keterangan Tambahan:</label>
                            <textarea name="remark" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitPhoto">Simpan Data Monitoring</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Background sedikit abu-abu untuk pembeda, tapi tanpa garis coret kawan */
.item-closed { background-color: #f8f9fa; } 
.item-closed td { color: #495057; } 
</style>
@endsection

@push('scripts')
<script>
let activeId;

function openEditModal(item) {
    activeId = item.id;
    $('[name="follow_up_note"]').val(item.follow_up_note);
    $('[name="execution_date"]').val(item.execution_date);
    $('[name="executed_by"]').val(item.executed_by);
    $('[name="verified_by"]').val(item.verified_by);
    $('[name="approved_by"]').val(item.approved_by);
    $('[name="remark"]').val(item.remark);
    $('#modalUploadPhoto').modal('show');
}

function handleStatusChange(select, id) {
    const newStatus = select.value;
    updateSimpleStatus(newStatus, id);
}

$(document).ready(function() {
    // Kita panggil DataTables menggunakan CLASS agar kedua tabel teraplikasikan kawan
    $('.datatable-followup').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/Indonesian.json" },
        "pageLength": 10,
        "scrollX": true,
        "order": [[2, 'desc']] // Urutkan berdasarkan tanggal cek
    });

    $('#formUploadPhoto').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = $('#btnSubmitPhoto');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: `{{ url('reports/pm/update-status') }}/${activeId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                location.reload(); 
            },
            error: function() {
                btn.prop('disabled', false).text('Simpan Data Monitoring');
                alert('Gagal simpan data kawan!');
            }
        });
    });
});

function updateSimpleStatus(status, id) {
    $.ajax({
        url: `{{ url('reports/pm/update-status') }}/${id}`,
        method: 'POST',
        data: JSON.stringify({ status: status }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function() {
            location.reload(); // Pas sukses ganti OK, dia akan otomatis reload dan pindah ke tabel bawah
        },
        error: function() {
            alert('Gagal mengubah status kawan!');
            location.reload();
        }
    });
}
</script>
@endpush