<?php
namespace App\Http\Controllers;

use App\Models\PmCheckItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function pmFollowUp()
    {
        // Ambil SEMUA data terlebih dahulu
        $allItems = PmCheckItem::with(['pmCheck.pmSchedule.asset', 'checklistTemplate'])
            ->whereNotNull('next_action')
            ->where('next_action', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        // Pisahkan data yang belum OK (Ongoing) dan sudah OK (History) kawan
        $ongoingItems = $allItems->where('follow_up_status', '!=', 'OK');
        $historyItems = $allItems->where('follow_up_status', 'OK');

        // Lempar dua variabel ini ke view
        return view('reports.pm.follow-up', compact('ongoingItems', 'historyItems'));
    }

    public function updateFollowUpStatus(Request $request, $id)
    {
        $item = PmCheckItem::findOrFail($id);

        // Update status HANYA jika ada di request (dari dropdown)
        if ($request->has('status')) {
            $item->follow_up_status = $request->status;
        }

        // Update kolom monitoring lainnya kawan
        $item->follow_up_note = $request->follow_up_note ?? $item->follow_up_note;
        $item->execution_date = $request->execution_date ?? $item->execution_date;
        $item->executed_by = $request->executed_by ?? $item->executed_by;
        $item->verified_by = $request->verified_by ?? $item->verified_by;
        $item->approved_by = $request->approved_by ?? $item->approved_by;
        $item->remark = $request->remark ?? $item->remark;

        if ($request->hasFile('photo_after')) {
            if ($item->photo_after) {
                Storage::disk('public')->delete($item->photo_after);
            }
            $item->photo_after = $request->file('photo_after')->store('pm-photos/after', 'public');
        }

        $item->save();
        return response()->json(['message' => 'Success']);
    }
}