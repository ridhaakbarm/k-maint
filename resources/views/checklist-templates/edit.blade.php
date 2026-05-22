@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="fas fa-edit me-1"></i> Form Edit Template Checklist
            </div>
            <form action="{{ route('pm.templates.update', $checklistTemplate->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jadwal PM (Pilih Aset) *</label>
                        <select class="form-select" name="pm_schedule_id" required>
                            @foreach($pmSchedules as $schedule)
                                <option value="{{ $schedule->id }}" data-schedule-type="{{ $schedule->schedule_type }}" {{ old('pm_schedule_id', $checklistTemplate->pm_schedule_id) == $schedule->id ? 'selected' : '' }}>
                                    {{ $schedule->asset->name ?? 'Mesin Tidak Ditemukan' }} - {{ str_replace('FA - ', '', $schedule->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Item Checklist *</label>
                            <input type="text" class="form-control" name="item_name" value="{{ old('item_name', $checklistTemplate->item_name) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Bagian yang Dicek *</label>
                            <input type="text" class="form-control" name="checked_part" value="{{ old('checked_part', $checklistTemplate->checked_part) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Instruksi Pengecekan *</label>
                        <textarea class="form-control" name="instructions" rows="2" required>{{ old('instructions', $checklistTemplate->instructions) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Standar Pengecekan *</label>
                        <textarea class="form-control" name="check_standard" rows="2" required>{{ old('check_standard', $checklistTemplate->check_standard) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Urutan</label>
                            <input type="number" class="form-control" name="order" value="{{ old('order', $checklistTemplate->order) }}">
                        </div>
                        <div class="col-md-4 mb-3 pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $checklistTemplate->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="weeklyScheduleBox">
                        <label class="form-label fw-bold">Update Jadwal Minggu (W1-W52)</label>
                        <div class="p-3 border rounded bg-light" style="max-height: 200px; overflow-y: auto;">
                            <div class="row g-2">
                                @php $activeWeeks = old('active_weeks', $checklistTemplate->active_weeks ?? []); @endphp
                                @for($i = 1; $i <= 52; $i++)
                                    <div class="col-2 col-md-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="active_weeks[]" id="week_{{ $i }}" value="{{ $i }}" {{ in_array($i, $activeWeeks) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="week_{{ $i }}">W{{ $i }}</label>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-success d-none" id="dailyScheduleInfo">
                        <i class="fas fa-calendar-day me-1"></i>
                        Template untuk PM Daily akan muncul setiap hari selama template aktif.
                    </div>
                </div>
                <div class="card-footer bg-white border-top">
                    <button type="submit" class="btn btn-warning px-4"><i class="fas fa-save"></i> Update Template</button>
                    <a href="{{ route('pm.templates.index') }}" class="btn btn-secondary px-4">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light fw-bold">Info Aset</div>
            <div class="card-body small">
                <p><strong>Nama Aset:</strong> {{ $checklistTemplate->pmSchedule->asset->name ?? '-' }}</p>
                <hr>
                <p class="mb-0 text-muted">Terakhir Update: {{ $checklistTemplate->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleSelect = document.querySelector('[name="pm_schedule_id"]');
    const weeklyBox = document.getElementById('weeklyScheduleBox');
    const dailyInfo = document.getElementById('dailyScheduleInfo');

    function toggleWeekSelector() {
        const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
        const isDaily = selectedOption && selectedOption.dataset.scheduleType === 'daily';
        weeklyBox.classList.toggle('d-none', isDaily);
        dailyInfo.classList.toggle('d-none', !isDaily);
    }

    scheduleSelect.addEventListener('change', toggleWeekSelector);
    toggleWeekSelector();
});
</script>
@endsection
