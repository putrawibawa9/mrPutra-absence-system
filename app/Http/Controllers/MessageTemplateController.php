<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageTemplateController extends Controller
{
    public function index()
    {
        $catalog = MessageTemplate::catalog();
        $templates = collect($catalog)->map(fn ($meta, $key) => (object) [
            'key' => $key,
            'name' => $meta['name'],
            'placeholders' => $meta['placeholders'],
            'body' => MessageTemplate::bodyFor($key),
            'is_custom' => MessageTemplate::query()->where('key', $key)->whereNotNull('body')->exists(),
        ])->values();

        return view('message-templates.index', compact('templates'));
    }

    public function update(Request $request, string $key)
    {
        abort_unless(array_key_exists($key, MessageTemplate::catalog()), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        MessageTemplate::query()->updateOrCreate(['key' => $key], ['body' => $data['body']]);

        return back()->with('status', 'Template "'.MessageTemplate::catalog()[$key]['name'].'" disimpan.');
    }

    public function reset(string $key)
    {
        abort_unless(array_key_exists($key, MessageTemplate::catalog()), 404);

        MessageTemplate::query()->where('key', $key)->delete();

        return back()->with('status', 'Template "'.MessageTemplate::catalog()[$key]['name'].'" dikembalikan ke default.');
    }
}
