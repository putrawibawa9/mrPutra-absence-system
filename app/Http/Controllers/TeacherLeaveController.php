<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherLeaveRequest;
use App\Models\TeacherLeave;
use App\Services\ScheduleMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeacherLeaveController extends Controller
{
    public function __construct(protected ScheduleMatchService $matcher)
    {
    }

    // ===== Admin =====

    public function index(Request $request)
    {
        $scope = in_array($request->input('scope'), ['pending', 'approved', 'rejected', 'all'], true)
            ? $request->input('scope')
            : 'pending';

        $leaves = TeacherLeave::query()
            ->with(['teacher:id,name', 'reviewer:id,name'])
            ->when($scope !== 'all', fn ($query) => $query->where('status', $scope))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        // Preload ketersediaan sekali; dan peta guru yang libur per tanggal
        // (pending/approved) supaya tidak direkomendasikan sebagai pengganti.
        $availabilities = $this->matcher->availableSlots();
        $leaveTeacherByDate = TeacherLeave::query()
            ->whereIn('status', [TeacherLeave::STATUS_PENDING, TeacherLeave::STATUS_APPROVED])
            ->get(['teacher_id', 'date'])
            ->groupBy(fn ($leave) => Carbon::parse($leave->date)->toDateString())
            ->map(fn ($rows) => $rows->pluck('teacher_id')->map(fn ($id) => (int) $id)->unique()->values()->all());

        $rows = $leaves->map(function (TeacherLeave $leave) use ($availabilities, $leaveTeacherByDate) {
            $exclude = array_values(array_unique(array_merge(
                [(int) $leave->teacher_id],
                $leaveTeacherByDate[Carbon::parse($leave->date)->toDateString()] ?? [],
            )));

            $subs = $this->matcher->matchForDayTime(
                $leave->weekdayKey(),
                $leave->isWholeDay() ? null : substr((string) $leave->start_time, 0, 5),
                $leave->isWholeDay() ? null : substr((string) $leave->end_time, 0, 5),
                $availabilities,
                $exclude,
            );

            return (object) [
                'leave' => $leave,
                'substitutes' => $subs,
                'substitute_count' => $subs->count(),
            ];
        });

        return view('teacher-leaves.index', [
            'rows' => $rows,
            'scope' => $scope,
            'pendingCount' => TeacherLeave::query()->where('status', TeacherLeave::STATUS_PENDING)->count(),
        ]);
    }

    public function approve(Request $request, TeacherLeave $teacher_leave)
    {
        $teacher_leave->update([
            'status' => TeacherLeave::STATUS_APPROVED,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan libur disetujui.');
    }

    public function reject(Request $request, TeacherLeave $teacher_leave)
    {
        $teacher_leave->update([
            'status' => TeacherLeave::STATUS_REJECTED,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan libur ditolak.');
    }

    // ===== Guru (self-service) =====

    public function myIndex()
    {
        $leaves = auth()->user()->teacherLeaves()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('teacher-leaves.my-index', compact('leaves'));
    }

    public function myCreate()
    {
        return view('teacher-leaves.my-create');
    }

    public function myStore(TeacherLeaveRequest $request)
    {
        TeacherLeave::create(array_merge($request->validated(), [
            'teacher_id' => $request->user()->id,
            'status' => TeacherLeave::STATUS_PENDING,
        ]));

        return redirect()->route('my-leave.index')->with('status', 'Pengajuan libur terkirim. Menunggu persetujuan admin.');
    }

    public function myDestroy(TeacherLeave $teacher_leave)
    {
        abort_unless($teacher_leave->teacher_id === auth()->id(), 403);
        abort_unless($teacher_leave->isPending(), 403, 'Hanya pengajuan yang masih menunggu yang bisa dibatalkan.');

        $teacher_leave->delete();

        return redirect()->route('my-leave.index')->with('status', 'Pengajuan libur dibatalkan.');
    }
}
