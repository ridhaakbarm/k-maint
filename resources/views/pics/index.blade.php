@extends('layouts.app')

@section('title', 'Management PIC')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Management PIC (Person In Charge)
                    </h3>
                    @if(auth()->user()->isAdmin())
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPic">
                            <i class="fas fa-plus"></i> Tambah PIC
                        </button>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    @if(!auth()->user()->isAdmin())
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Akses Ditolak!</strong> Hanya Administrator yang dapat mengakses halaman ini.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table id="pics-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama PIC</th>
                                    <th>Bagian/Department</th>
                                    <th width="10%">Status</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data akan di-load oleh DataTables -->
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin())
<!-- Modal Add/Edit PIC -->
<div class="modal fade" id="modalAddPic" tabindex="-1" aria-labelledby="modalAddPicLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddPicLabel">Tambah PIC Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPic">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="pic_id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama PIC <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name_error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Bagian/Department <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="department" name="department" required>
                        <div class="invalid-feedback" id="department_error"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">
                                Status Aktif
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete Confirmation -->
<div class="modal fade" id="modalDeletePic" tabindex="-1" aria-labelledby="modalDeletePicLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDeletePicLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus PIC <strong id="deletePicName"></strong>?</p>
                <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .card-title {
        font-weight: 600;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .btn-group .btn {
        margin-right: 5px;
    }
    .btn-group .btn:last-child {
        margin-right: 0;
    }
    .badge {
        font-size: 0.85em;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    @if(auth()->user()->isAdmin())
    // Initialize DataTables hanya untuk admin
    var table = $('#pics-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('pics.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'department', name: 'department' },
            { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            processing: "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
            search: "Cari:",
            lengthMenu: "Tampil _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Data tidak ditemukan",
            emptyTable: "Tidak ada data tersedia",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        responsive: true
    });

    // Fungsi untuk membersihkan modal backdrop
    function cleanupModalBackdrop() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
    }

    // Reset form dan cleanup ketika modal ditutup
    $('#modalAddPic').on('hidden.bs.modal', function() {
        $('#formPic')[0].reset();
        $('#pic_id').val('');
        $('#modalAddPicLabel').text('Tambah PIC Baru');
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        $('#is_active').prop('checked', true);
        
        // Cleanup modal backdrop
        setTimeout(cleanupModalBackdrop, 100);
    });

    // Cleanup untuk modal delete
    $('#modalDeletePic').on('hidden.bs.modal', function() {
        setTimeout(cleanupModalBackdrop, 100);
    });

    // Handle form submit dengan perbaikan modal close
    $('#formPic').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var id = $('#pic_id').val();
        var url = id ? "{{ url('pics') }}/" + id : "{{ route('pics.store') }}";
        var method = id ? 'PUT' : 'POST';

        // Clear previous errors
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            beforeSend: function() {
                $('#btnSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(response) {
                if (response.success) {
                    // Tutup modal dengan proper cleanup
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalAddPic'));
                    modal.hide();
                    
                    // Cleanup manual
                    cleanupModalBackdrop();
                    
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                $('#btnSave').prop('disabled', false).html('Simpan');
                
                if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Akses Ditolak!',
                        text: 'Anda tidak memiliki akses untuk melakukan aksi ini.',
                        timer: 3000
                    });
                    $('#modalAddPic').modal('hide');
                    return;
                }
                
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('#' + key + '_error').text(value[0]);
                        $('#' + key).addClass('is-invalid');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat menyimpan data.',
                        timer: 3000
                    });
                }
            },
            complete: function() {
                $('#btnSave').prop('disabled', false).html('Simpan');
            }
        });
    });

    // Handle edit button click
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('pics') }}/" + id,
            method: 'GET',
            success: function(response) {
                $('#pic_id').val(response.id);
                $('#name').val(response.name);
                $('#department').val(response.department);
                $('#is_active').prop('checked', response.is_active);
                
                $('#modalAddPicLabel').text('Edit PIC');
                
                // Show modal dengan Bootstrap 5
                var modal = new bootstrap.Modal(document.getElementById('modalAddPic'));
                modal.show();
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Akses Ditolak!',
                        text: 'Anda tidak memiliki akses untuk mengedit data.',
                        timer: 3000
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Gagal memuat data PIC.',
                        timer: 3000
                    });
                }
            }
        });
    });

    // Handle delete button click
 // Handle delete button click
var deleteId;
$(document).on('click', '.btn-delete', function() {
    deleteId = $(this).data('id');
    var name = $(this).data('name');
    
    $('#deletePicName').text(name);

    var modal = new bootstrap.Modal(document.getElementById('modalDeletePic'));
    modal.show();
});

// Handle confirm delete
$('#btnConfirmDelete').on('click', function() {

    $.ajax({
        url: "{{ url('pics') }}/" + deleteId,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('#btnConfirmDelete').prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Menghapus...');
        },
        success: function(response) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalDeletePic'));
            modal.hide();
            cleanupModalBackdrop();

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                table.ajax.reload(null, false); // reload DataTables
            }
        },
        error: function(xhr) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalDeletePic'));
            modal.hide();
            cleanupModalBackdrop();

            let msg = "Gagal menghapus data PIC.";
            if (xhr.status === 403) {
                msg = "Anda tidak memiliki akses untuk menghapus data.";
            } else if (xhr.responseJSON?.message) {
                msg = xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: msg,
                timer: 2500
            });
        },
        complete: function() {
            $('#btnConfirmDelete').prop('disabled', false).html('Hapus');
        }
    });

});


    // Remove validation on input change
    $('.form-control').on('input', function() {
        if ($(this).hasClass('is-invalid')) {
            $(this).removeClass('is-invalid');
            $('#' + $(this).attr('id') + '_error').text('');
        }
    });

    // Force cleanup jika ada masalah dengan modal backdrop
    $(document).on('click', function(e) {
        // Jika klik di area yang redup dan tidak ada modal yang terbuka
        if ($(e.target).hasClass('modal-backdrop')) {
            cleanupModalBackdrop();
        }
    });

    // Initialize tooltips
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    @endif
});
</script>
@endpush