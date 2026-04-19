<?php

namespace App\Http\Controllers;

use App\Models\Progress;
use App\Models\Project;
use Illuminate\View\View;

class JokiDashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalProjects = Project::query()->count();
        $totalRevenue = (int) Project::query()->sum('total_cost');
        $latestProgressUpdates = Progress::query()
            ->with('project')
            ->latest('created_at')
            ->take(8)
            ->get();

        return view('joki.dashboard', compact(
            'totalProjects',
            'totalRevenue',
            'latestProgressUpdates',
        ));
    }
}
