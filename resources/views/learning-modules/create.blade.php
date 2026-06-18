<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Add Module</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('learning-modules.store') }}" data-confirm="Save this module? Please double check the module name and price first.">
            @include('learning-modules._form', [
                'submitLabel' => 'Save Module',
            ])
        </form>
    </div>
</x-app-layout>
