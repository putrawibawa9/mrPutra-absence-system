<?php

namespace App\Http\Controllers;

use App\Http\Requests\LearningModuleRequest;
use App\Models\LearningModule;

class LearningModuleController extends Controller
{
    public function index()
    {
        $modules = LearningModule::query()->latest()->paginate(10);

        return view('learning-modules.index', compact('modules'));
    }

    public function create()
    {
        return view('learning-modules.create');
    }

    public function store(LearningModuleRequest $request)
    {
        LearningModule::create([
            'name' => $request->string('name')->toString(),
            'price' => $request->integer('price'),
            'notes' => $request->string('notes')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('learning-modules.index')->with('status', 'Module berhasil ditambahkan.');
    }

    public function edit(LearningModule $learning_module)
    {
        return view('learning-modules.edit', compact('learning_module'));
    }

    public function update(LearningModuleRequest $request, LearningModule $learning_module)
    {
        $learning_module->update([
            'name' => $request->string('name')->toString(),
            'price' => $request->integer('price'),
            'notes' => $request->string('notes')->toString(),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('learning-modules.index')->with('status', 'Module berhasil diperbarui.');
    }

    public function destroy(LearningModule $learning_module)
    {
        $learning_module->delete();

        return redirect()->route('learning-modules.index')->with('status', 'Module berhasil dihapus.');
    }
}
