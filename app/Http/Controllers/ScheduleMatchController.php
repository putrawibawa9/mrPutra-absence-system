<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\ScheduleMatchService;
use Illuminate\Http\Request;

class ScheduleMatchController extends Controller
{
    public function __construct(protected ScheduleMatchService $matcher)
    {
    }

    public function index(Request $request)
    {
        $scope = $request->input('scope') === 'all' ? 'all' : 'pending';

        $registrations = Registration::query()
            ->when($scope === 'pending', fn ($query) => $query->where('status', Registration::STATUS_PENDING))
            ->latest()
            ->get();

        // Preload ketersediaan guru sekali untuk semua registrasi.
        $availabilities = $this->matcher->availableSlots();
        $hasAvailabilityData = $availabilities->isNotEmpty();

        $rows = $registrations->map(function (Registration $registration) use ($availabilities) {
            $matches = $this->matcher->matchForRegistration($registration, $availabilities);

            return (object) [
                'registration' => $registration,
                'matches' => $matches,
                'match_count' => $matches->count(),
                'incomplete' => empty($registration->available_days) || empty($registration->time_preferences),
            ];
        });

        return view('schedule-match.index', [
            'rows' => $rows,
            'scope' => $scope,
            'hasAvailabilityData' => $hasAvailabilityData,
            'matchedCount' => $rows->where('match_count', '>', 0)->count(),
        ]);
    }
}
