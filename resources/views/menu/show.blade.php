<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">{{ $menu['label'] }}</h2>
            @if (! empty($menu['description']))
                <p class="text-sm text-slate-500">{{ $menu['description'] }}</p>
            @endif
        </div>
    </x-slot>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
               class="block rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-base font-semibold text-slate-900">{{ $item['label'] }}</p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-300" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </div>
                @if (! empty($item['description']))
                    <p class="mt-1 text-sm text-slate-500">{{ $item['description'] }}</p>
                @endif
            </a>
        @endforeach
    </div>
</x-app-layout>
