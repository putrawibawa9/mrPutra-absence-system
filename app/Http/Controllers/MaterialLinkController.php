<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialLinkRequest;
use App\Models\MaterialLink;

class MaterialLinkController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $materialLinks = MaterialLink::query()
            ->withCount('teacherSchedules')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('url', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('material-links.index', compact('materialLinks', 'filters'));
    }

    public function create()
    {
        return view('material-links.create', [
            'materialLink' => null,
        ]);
    }

    public function store(MaterialLinkRequest $request)
    {
        MaterialLink::create($request->validated());

        return redirect()->route('material-links.index')->with('status', 'Link materi berhasil ditambahkan.');
    }

    public function edit(MaterialLink $material_link)
    {
        return view('material-links.edit', [
            'materialLink' => $material_link,
        ]);
    }

    public function update(MaterialLinkRequest $request, MaterialLink $material_link)
    {
        $material_link->update($request->validated());

        return redirect()->route('material-links.index')->with('status', 'Link materi berhasil diperbarui.');
    }

    public function destroy(MaterialLink $material_link)
    {
        $material_link->delete();

        return redirect()->route('material-links.index')->with('status', 'Link materi berhasil dihapus.');
    }
}
