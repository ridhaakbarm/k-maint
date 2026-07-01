<tr id="item-row-{{ $item->id }}" class="{{ $item->follow_up_status == 'OK' ? 'item-closed' : '' }}">
    <td class="ps-3">{{ $loop->iteration }}</td>
    <td class="text-center">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('pm.execution.show', $item->pm_check_id) }}" class="btn btn-outline-primary" target="_blank"><i class="fas fa-eye"></i></a>
            @php
                $dept = strtolower(trim((string) Auth::user()->department));
                $canCreateInternalTicket = Auth::user()->isAdmin() || Auth::user()->isMTC() || in_array($dept, ['maintenance', 'engineering', 'mtc']);
            @endphp
            @if($canCreateInternalTicket)
                <a href="{{ route('internal-tickets.create', ['pm_check_item_id' => $item->id]) }}" class="btn btn-outline-info" title="Buat Tiket Internal">
                    <i class="fas fa-clipboard-check"></i>
                </a>
            @endif
            <button type="button" class="btn btn-primary" onclick="openEditModal({{ json_encode($item) }})">
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>
    </td>
    <td>{{ $item->pmCheck->check_date->format('d/m/Y') }}</td>
    <td><strong>{{ $item->pmCheck->pmSchedule->asset->name ?? '-' }}</strong></td>
    <td>{{ $item->checklistTemplate->item_name }}</td>
    <td>{{ $item->checklistTemplate->standard_action ?? '-' }}</td>
    <td><mark class="bg-warning-light text-danger fw-bold">{{ $item->next_action }}</mark></td>
    
    <td>{{ $item->follow_up_note ?? '-' }}</td>
    <td>{{ $item->execution_date ? date('d/m/Y', strtotime($item->execution_date)) : '-' }}</td>
    <td>{{ $item->executed_by ?? '-' }}</td>
    <td>{{ $item->verified_by ?? '-' }}</td>
    <td>{{ $item->approved_by ?? '-' }}</td>
    
    <td id="photo-cell-{{ $item->id }}" class="text-center">
        @if($item->photo_after)
            <a href="{{ asset('storage/' . $item->photo_after) }}" target="_blank" class="btn btn-sm btn-info text-white"><i class="fas fa-image"></i></a>
        @else - @endif
    </td>
    <td>
        <select class="form-select form-select-sm fw-bold px-1 border-{{ $item->follow_up_status == 'OK' ? 'success' : 'warning' }}" 
                style="font-size: 0.75rem;" 
                onchange="handleStatusChange(this, {{ $item->id }})">
            <option value="Not OK" {{ $item->follow_up_status == 'Not OK' ? 'selected' : '' }}>Not OK</option>
            <option value="OK" {{ $item->follow_up_status == 'OK' ? 'selected' : '' }}>OK</option>
        </select>
    </td>
    <td>{{ $item->remark ?? '-' }}</td>
</tr>
