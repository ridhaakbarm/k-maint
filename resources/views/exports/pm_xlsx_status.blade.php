@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-file-excel text-success me-2"></i>Export PM XLSX
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="small text-muted">Periode</div>
                <div class="fw-bold">{{ $startDate }} s/d {{ $endDate }}</div>
            </div>

            <div class="alert alert-info d-flex align-items-center gap-3" id="exportStatusBox">
                <div class="spinner-border spinner-border-sm" role="status" id="exportSpinner"></div>
                <div>
                    <div class="fw-bold" id="exportStatusTitle">Sedang membuat file XLSX</div>
                    <div class="small" id="exportStatusMessage">Halaman ini akan mengecek status otomatis.</div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('monitoring.pm') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
                <a href="#" class="btn btn-success disabled" id="downloadButton" aria-disabled="true">
                    <i class="fas fa-download me-1"></i>Download XLSX
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const statusUrl = @json($statusUrl);
const statusBox = document.getElementById('exportStatusBox');
const statusTitle = document.getElementById('exportStatusTitle');
const statusMessage = document.getElementById('exportStatusMessage');
const spinner = document.getElementById('exportSpinner');
const downloadButton = document.getElementById('downloadButton');

async function checkExportStatus() {
    try {
        const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();

        statusMessage.textContent = data.message || 'Status export sedang dicek.';

        if (data.status === 'done') {
            statusBox.className = 'alert alert-success d-flex align-items-center gap-3';
            statusTitle.textContent = 'File XLSX sudah siap';
            spinner.style.display = 'none';
            downloadButton.href = data.download_url;
            downloadButton.classList.remove('disabled');
            downloadButton.removeAttribute('aria-disabled');
            return;
        }

        if (data.status === 'failed') {
            statusBox.className = 'alert alert-danger d-flex align-items-center gap-3';
            statusTitle.textContent = 'Export gagal';
            spinner.style.display = 'none';
            return;
        }
    } catch (error) {
        statusMessage.textContent = 'Belum bisa membaca status. Mencoba lagi...';
    }

    setTimeout(checkExportStatus, 5000);
}

setTimeout(checkExportStatus, 5000);
</script>
@endsection
