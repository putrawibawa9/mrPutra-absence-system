<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Game</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('games.update', $game) }}">
            @method('PUT')
            @include('games._form', ['submitLabel' => 'Perbarui Game'])
        </form>
    </div>
</x-app-layout>
