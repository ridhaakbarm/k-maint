{{-- resources/views/monitoring/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <h5 class="mb-0 fw-bold"><i class="fas fa-desktop me-2"></i> LIVE MONITORING TEKNISI</h5>
                <span class="badge bg-danger pulse">REAL-TIME</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Teknisi</th>
                                <th>Kategori</th>
                                <th>Pekerjaan / Mesin</th>
                                <th>Mulai Jam</th>
                                <th>Durasi Jalan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeActivities as $act)
                            <tr>
                                <td><strong>{{ $act->user->name }}</strong></td>
                                <td><span class="badge bg-info">{{ $act->category }}</span></td>
                                <td>
                                    @if($act->category == 'PM')
                                        {{ $act->pmCheck->pmSchedule->asset->name }}
                                    @elseif($act->category == 'Breakdown')
                                        {{ $act->ticket->asset->name }}
                                    @else
                                        {{ $act->description }}
                                    @endif
                                </td>
                                <td>{{ $act->start_time->format('H:i') }}</td>
                                <td><span class="text-primary fw-bold">{{ $act->start_time->diffInMinutes(now()) }} Menit</span></td>
                                <td><span class="badge bg-success shadow-sm">WORKING...</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pulse {
    animation: pulse-animation 2s infinite;
}
@keyframes pulse-animation {
    0% { box-shadow: 0 0 0 0px rgba(220, 53, 69, 0.7); }
    100% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
}
</style>
@endsection