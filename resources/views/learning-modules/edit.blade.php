<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Edit Module</h2>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('learning-modules.update', $learning_module) }}" data-confirm="Update this module? Please double check the module name and price first.">
            @method('PUT')
            @include('learning-modules._form', [
                'submitLabel' => 'Update Module',
            ])
        </form>
    </div>
</x-app-layout>
