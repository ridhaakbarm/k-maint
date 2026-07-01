<div class="card shadow-sm h-100">
    <div class="card-header bg-{{ $color }} {{ $color === 'info' || $color === 'warning' ? 'text-dark' : 'text-white' }} fw-bold">
        <i class="fas {{ $icon }} me-2"></i>{{ $title }}
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($items as $check)
                <a href="{{ route('pm.execution.show', $check->id) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <strong class="text-primary">{{ $check->pmSchedule->asset->name ?? '-' }}</strong>
                            <small class="d-block text-muted">
                                {{ $check->pmSchedule->name ?? '-' }}
                            </small>
                            <small class="d-block">
                                <i class="fas fa-user me-1 text-muted"></i>{{ $check->technician->name ?? $check->technician_name ?? '-' }}
                            </small>
                        </div>
                        <span class="badge bg-light text-dark border">
                            {{ strtoupper(str_replace('_', ' ', $check->status)) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                        <span>Due: {{ $check->due_date ? \Carbon\Carbon::parse($check->due_date)->format('d/m/Y') : '-' }}</span>
                        <span>{{ $check->check_date ? \Carbon\Carbon::parse($check->check_date)->format('d/m/Y') : 'Belum dicek' }}</span>
                    </div>
                </a>
            @empty
                <div class="text-center text-muted py-4">{{ $empty }}</div>
            @endforelse
        </div>
    </div>
</div>
