@extends('layouts.app')

@section('content')
@php
    $selectedSchedule = old('pm_schedule_id', $preselected_schedule);
    $defaultOrder = old('order', $selectedSchedule ? ($nextOrders[$selectedSchedule] ?? 0) : 0);
@endphp
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white text-primary fw-bold">
                <i class="fas fa-plus-circle me-1"></i> Form Tambah Template Checklist
            </div>
            <form action="{{ route('pm.templates.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jadwal PM (Pilih Aset) *</label>
                        <select class="form-select @error('pm_schedule_id') is-invalid @enderror" name="pm_schedule_id" required>
                            <option value="">-- Pilih Jadwal & Mesin --</option>
                            @foreach($pmSchedules as $schedule)
                                <option value="{{ $schedule->id }}" data-schedule-type="{{ $schedule->schedule_type }}" {{ old('pm_schedule_id', $preselected_schedule) == $schedule->id ? 'selected' : '' }}>
                                    {{ $schedule->asset->name ?? 'Mesin Tidak Ditemukan' }} - {{ str_replace('FA - ', '', $schedule->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Item Checklist *</label>
                            <input type="text" class="form-control" name="item_name" value="{{ old('item_name') }}" placeholder="Contoh: Motor Utama" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Bagian yang Dicek *</label>
                            <input type="text" class="form-control" name="checked_part" value="{{ old('checked_part') }}" placeholder="Contoh: Area Gearbox" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Instruksi Pengecekan *</label>
                        <textarea class="form-control" name="instructions" rows="2" required>{{ old('instructions') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Standar Pengecekan *</label>
                        <textarea class="form-control" name="check_standard" rows="2" required>{{ old('check_standard') }}</textarea>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Urutan *</label>
                            <input type="number" class="form-control" name="order" value="{{ $defaultOrder }}" required readonly>
                        </div>
                        <div class="col-md-6 mb-3 pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Template Aktif</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="weeklyScheduleBox">
                        <label class="d-block fw-bold mb-2">Jadwal Kemunculan (Week 1 - 52) *</label>
                        <div class="btn-group btn-group-sm mb-2">
                            <button type="button" class="btn btn-outline-secondary" id="btnAll">Pilih Semua</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnGanjil">Ganjil</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnGenap">Genap</button>
                            <button type="button" class="btn btn-outline-danger" id="btnReset">Reset</button>
                        </div>
                        <div class="p-3 border rounded bg-light" style="max-height: 200px; overflow-y: auto;">
                            <div class="row g-2">
                                @for($i = 1; $i <= 52; $i++)
                                    <div class="col-3 col-sm-2 col-md-1">
                                        <div class="form-check">
                                            <input class="form-check-input week-check" type="checkbox" name="active_weeks[]" id="week_{{ $i }}" value="{{ $i }}" {{ (is_array(old('active_weeks')) && in_array($i, old('active_weeks'))) ? 'checked' : '' }}>
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
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Simpan Template</button>
                    <a href="{{ route('pm.templates.index') }}" class="btn btn-secondary px-4">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.week-check');
        document.getElementById('btnAll').addEventListener('click', () => checkboxes.forEach(cb => cb.checked = true));
        document.getElementById('btnReset').addEventListener('click', () => checkboxes.forEach(cb => cb.checked = false));
        document.getElementById('btnGanjil').addEventListener('click', () => checkboxes.forEach(cb => cb.checked = (parseInt(cb.value) % 2 !== 0)));
        document.getElementById('btnGenap').addEventListener('click', () => checkboxes.forEach(cb => cb.checked = (parseInt(cb.value) % 2 === 0)));

        const scheduleSelect = document.querySelector('[name="pm_schedule_id"]');
        const orderInput = document.querySelector('[name="order"]');
        const nextOrders = @json($nextOrders);
        const weeklyBox = document.getElementById('weeklyScheduleBox');
        const dailyInfo = document.getElementById('dailyScheduleInfo');

        function toggleWeekSelector() {
            const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
            const isDaily = selectedOption && selectedOption.dataset.scheduleType === 'daily';
            weeklyBox.classList.toggle('d-none', isDaily);
            dailyInfo.classList.toggle('d-none', !isDaily);
        }

        function updateOrder() {
            orderInput.value = nextOrders[scheduleSelect.value] ?? 0;
        }

        scheduleSelect.addEventListener('change', function() {
            toggleWeekSelector();
            updateOrder();
        });
        toggleWeekSelector();
        updateOrder();
    });
</script>
@endsection
