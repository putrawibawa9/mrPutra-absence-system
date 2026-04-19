<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->with('latestProgress')
            ->latest('updated_at')
            ->paginate(10);

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::query()->create($this->validatedData($request));

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'latestProgress',
            'progresses' => fn ($query) => $query->latest('created_at'),
        ]);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validatedData($request));

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Proyek berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'project_title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'total_cost' => ['required', 'integer', 'min:0'],
        ]);
    }
}
