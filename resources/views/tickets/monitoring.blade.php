@extends('layouts.app')

@section('content')
<div class="container-fluid" style="position: relative; z-index: 1;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Monitoring Tiket Teknik</h3>
        <form method="GET" action="{{ route('tickets.monitoring') }}" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" style="width: 100px;">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
    </div>

    {{-- BARIS 1: KANBAN BOARD --}}
    <div class="row g-3 mb-5">
        @php
            $columns = [
                ['title' => 'Open', 'bg' => 'danger', 'data' => $open],
                ['title' => 'Assigned', 'bg' => 'warning', 'data' => $assigned],
                ['title' => 'Pending', 'bg' => 'secondary', 'data' => $pending],
                ['title' => 'Request to Close', 'bg' => 'primary', 'data' => $request_to_close],
                ['title' => 'Closed', 'bg' => 'success', 'data' => $closed],
            ];
        @endphp

        @foreach($columns as $col)
        <div class="col"> {{-- Diubah menjadi 'col' agar fleksibel --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                <div class="card-header bg-{{ $col['bg'] }} {{ in_array($col['bg'], ['warning', 'secondary']) ? 'text-dark' : 'text-white' }} fw-bold">
                    {{ $col['title'] }} ({{ $col['data']->count() }})
                </div>
                <div class="card-body bg-light p-2" style="min-height: 500px; max-height: 700px; overflow-y: auto;">
                    @forelse($col['data'] as $t)
                        <div class="card mb-2 border-0 shadow-sm rounded-3">
                            <div class="card-body p-2" style="font-size: 0.85rem;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="badge bg-light text-dark border small">{{ $t->ticket_no }}</span>
                                    <a href="{{ route('tickets.show', $t) }}" class="text-muted"><i class="fas fa-eye"></i></a>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">{{ Str::limit($t->subject, 30) }}</h6>

                                {{-- PENANDA JEDA (Tiket onprogress yang sedang dihentikan) --}}
                                @if(method_exists($t, 'isPaused') && $t->isPaused())
                                    <div class="mb-2">
                                        <span class="badge bg-dark x-small shadow-sm">
                                            <i class="fas fa-pause-circle me-1"></i>JEDA
                                        </span>
                                    </div>
                                @endif

                                {{-- PENANDA RE-WORK (Jika pernah ditolak) --}}
                                @if($t->was_rejected)
                                    <div class="mb-2">
                                        <span class="badge bg-danger x-small shadow-sm">
                                            <i class="fas fa-exclamation-triangle me-1"></i>REJECTED TICKET
                                        </span>
                                    </div>
                                @endif

                                <div class="text-muted mb-2">
                                    <i class="fas fa-user-circle me-1"></i> {{ $t->requester->name }}<br>
                                    <i class="fas fa-cog me-1"></i> {{ $t->asset->name ?? 'No Asset' }}
                                </div>
                                <div class="pt-1 border-top d-flex justify-content-between x-small text-muted">
                                    <span><i class="far fa-calendar"></i> {{ $t->created_at->format('d/m/y') }}</span>
                                    @if($t->mtc_pic_name) <span><i class="fas fa-wrench"></i> {{ $t->mtc_pic_name }}</span> @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted small italic">Kosong</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- BARIS 2: NEED TO FOLLOW UP --}}
    @if(isset($needFollowUp) && $needFollowUp->isNotEmpty())
    <div class="row g-3 mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-warning text-dark fw-bold py-3">
                    <i class="fas fa-calendar-check me-2"></i>
                    Need to Follow Up ({{ $needFollowUp->count() }} Tiket)
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        @foreach($needFollowUp as $t)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-warning shadow-sm rounded-3 h-100 {{ \Carbon\Carbon::parse($t->estimated_date)->isPast() ? 'border-danger' : '' }}">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-light text-dark border small">{{ $t->ticket_no }}</span>
                                            <a href="{{ route('tickets.show', $t) }}" class="text-muted ms-2"><i class="fas fa-eye"></i></a>
                                        </div>
                                        {{-- Status Badge --}}
                                        <span class="badge
                                            @if($t->status == 'pending') bg-secondary
                                            @elseif($t->status == 'onprogress') bg-warning text-dark
                                            @else bg-info text-dark @endif">
                                            {{ ucfirst($t->status) }}
                                        </span>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-2">{{ Str::limit($t->subject, 35) }}</h6>

                                    {{-- Informasi Follow Up --}}
                                    <div class="alert alert-warning alert-dismissible fade show p-2 mb-2" role="alert">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-calendar-check fa-lg"></i>
                                            <div>
                                                <div class="fw-bold {{ \Carbon\Carbon::parse($t->estimated_date)->isPast() ? 'text-danger' : 'text-primary' }}">
                                                    {{ \Carbon\Carbon::parse($t->estimated_date)->format('d/m/Y') }}
                                                </div>
                                                @if(\Carbon\Carbon::parse($t->estimated_date)->isPast())
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-circle"></i> Lewat Tanggal!
                                                    </small>
                                                @else
                                                    @php
                                                        $daysUntil = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($t->estimated_date), false);
                                                    @endphp
                                                    <small class="text-muted">
                                                        {{ $daysUntil > 0 ? "{$daysUntil} hari lagi" : 'Hari ini' }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- PR Number --}}
                                    @if($t->pr_number)
                                        <div class="mb-2">
                                            <span class="badge bg-primary small">
                                                <i class="fas fa-file-invoice me-1"></i>
                                                {{ $t->pr_number }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Detail Tiket --}}
                                    <div class="text-muted mb-2" style="font-size: 0.8rem;">
                                        <div><i class="fas fa-user-circle me-1"></i> {{ $t->requester->name }}</div>
                                        <div><i class="fas fa-cog me-1"></i> {{ $t->asset->name ?? 'No Asset' }}</div>
                                        @if($t->mtc_pic_name)
                                            <div><i class="fas fa-wrench me-1"></i> {{ $t->mtc_pic_name }}</div>
                                        @endif
                                    </div>

                                    {{-- Action Button --}}
                                    <a href="{{ route('tickets.show', $t) }}" class="btn btn-sm btn-warning w-100 fw-bold">
                                        <i class="fas fa-hand-pointer me-1"></i> Follow Up
                                    </a>
                                </div>
                            </div>
                             </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- BARIS 3: TOTAL TIKET PER ASSIGNEE --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white fw-bold py-3 border-bottom">
            <i class="fas fa-users me-2 text-primary"></i>Total Ticket Per Assignee
        </div>
        <div class="card-body p-4">
            @foreach($technicianStats as $stat)
            <div class="row align-items-center mb-4">
                <div class="col-md-2 fw-bold text-uppercase small">{{ $stat['name'] }}</div>
                <div class="col-md-9">
                    <div class="progress" style="height: 20px; border-radius: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $stat['percentage'] }}%;" 
                             aria-valuenow="{{ $stat['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                             {{ $stat['percentage'] }}%
                        </div>
                    </div>
                </div>
                <div class="col-md-1 text-end fw-bold">{{ $stat['count'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.7rem; }
    .bg-light { background-color: #f8f9fa !important; }
    /* Custom Scrollbar kawan */
    .card-body::-webkit-scrollbar { width: 4px; }
    .card-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    /* Fix card overlap issue */
    .card { position: relative; z-index: 1; }
    .card:hover { z-index: 2; }
</style>
@endsection