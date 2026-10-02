<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class MenuController extends Controller
{
    /**
     * Halaman hub sebuah grup menu: menampilkan sub-menu sebagai kartu,
     * supaya sidebar tetap ramping (grup = satu link).
     */
    public function show(Request $request, string $section)
    {
        $user = $request->user();
        $menu = Navigation::section($user, $section);

        abort_if($menu === null, 404);

        // Hanya item yang route-nya benar-benar terdaftar & bisa diakses.
        $items = collect($menu['items'])
            ->filter(fn ($item) => empty($item['disabled']) && Route::has($item['route']))
            ->values();

        // Grup satu-item tidak butuh hub — langsung ke halaman itu.
        if ($items->count() === 1) {
            return redirect()->route($items->first()['route']);
        }

        abort_if($items->isEmpty(), 404);

        return view('menu.show', [
            'menu' => $menu,
            'items' => $items,
        ]);
    }
}
