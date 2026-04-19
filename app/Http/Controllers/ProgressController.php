<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'progress_note' => ['required', 'string'],
            'progress_percentage' => ['required', 'integer', 'between:0,100'],
        ]);

        $project->progresses()->create($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Progress proyek berhasil ditambahkan.');
    }
}
