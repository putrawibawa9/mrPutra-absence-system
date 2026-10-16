<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameRequest;
use App\Models\Game;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::query()->orderBy('tema')->paginate(15);

        return view('games.index', compact('games'));
    }

    public function create()
    {
        return view('games.create');
    }

    public function store(GameRequest $request)
    {
        Game::create([
            'tema' => $request->string('tema')->toString(),
            'keterangan' => $request->string('keterangan')->toString() ?: null,
            'link' => $request->string('link')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('games.index')->with('status', 'Game berhasil ditambahkan.');
    }

    public function edit(Game $game)
    {
        return view('games.edit', compact('game'));
    }

    public function update(GameRequest $request, Game $game)
    {
        $game->update([
            'tema' => $request->string('tema')->toString(),
            'keterangan' => $request->string('keterangan')->toString() ?: null,
            'link' => $request->string('link')->toString(),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('games.index')->with('status', 'Game berhasil diperbarui.');
    }

    public function destroy(Game $game)
    {
        // Game yang pernah dipakai di sesi tidak dihapus — cukup dinonaktifkan
        // supaya riwayat sesi tetap utuh.
        if ($game->isInUse()) {
            $game->update(['is_active' => false]);

            return redirect()->route('games.index')->with('status', 'Game pernah dipakai di sesi, jadi dinonaktifkan (bukan dihapus).');
        }

        $game->delete();

        return redirect()->route('games.index')->with('status', 'Game berhasil dihapus.');
    }
}
