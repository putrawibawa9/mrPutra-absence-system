@php($mode = $mode ?? 'admin')
@php($legend = $legend ?? [])

@if (! empty($calendar['colors']))
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($calendar['colors'] as $key => $hex)
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">
                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $hex }}"></span>
                {{ $legend[$key] ?? '-' }}
            </span>
        @endforeach
    </div>
@endif

@if ($calendar['isEmpty'])
    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">Belum ada jadwal untuk ditampilkan di kalender.</p>
@else
    <div class="overflow-x-auto">
        <div style="min-width: 760px;">
            {{-- Header hari --}}
            <div class="grid" style="grid-template-columns: 56px repeat(7, minmax(0, 1fr));">
                <div></div>
                @foreach ($calendar['days'] as $day)
                    <div class="border-b border-slate-100 pb-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $day['label'] }}</div>
                @endforeach
            </div>

            {{-- Body grid --}}
            <div class="grid" style="grid-template-columns: 56px repeat(7, minmax(0, 1fr));">
                {{-- Gutter jam --}}
                <div style="position: relative; height: {{ $calendar['gridHeight'] }}px;">
                    @foreach ($calendar['hours'] as $h)
                        <div class="text-[11px] text-slate-400" style="position: absolute; right: 6px; top: {{ ($h * 60 - $calendar['startMin']) / 60 * $calendar['hourHeight'] - 6 }}px;">{{ sprintf('%02d:00', $h) }}</div>
                    @endforeach
                </div>

                {{-- Kolom hari --}}
                @foreach ($calendar['days'] as $day)
                    <div class="border-l border-slate-100" style="position: relative; height: {{ $calendar['gridHeight'] }}px;">
                        @foreach ($calendar['hours'] as $h)
                            <div class="border-t border-slate-100" style="position: absolute; left: 0; right: 0; top: {{ ($h * 60 - $calendar['startMin']) / 60 * $calendar['hourHeight'] }}px;"></div>
                        @endforeach

                        @foreach ($day['events'] as $e)
                            @php($schedule = $e['s'])
                            <a href="{{ $mode === 'admin' ? route('teacher-schedules.edit', $schedule) : '#' }}"
                                @if ($mode !== 'admin') onclick="return false;" @endif
                                title="{{ $schedule->timeRangeLabel() }}{{ $mode === 'admin' ? ' — '.$schedule->teacher->name : '' }}{{ $schedule->classroom ? ' — '.$schedule->classroom->name.' ('.$schedule->classroom->studentHint().')' : '' }}"
                                class="block overflow-hidden rounded-lg px-2 py-1 text-[11px] leading-tight text-white shadow-sm"
                                style="position: absolute; top: {{ $e['top'] }}px; height: {{ $e['height'] }}px; left: calc({{ $e['left'] }}% + 2px); width: calc({{ $e['width'] }}% - 4px); background: {{ $e['color'] }};{{ $schedule->is_active ? '' : ' opacity: 0.45;' }}">
                                <span class="block truncate font-semibold">{{ $schedule->timeRangeLabel() }}</span>
                                @if ($mode === 'admin')
                                    <span class="block truncate">{{ $schedule->teacher->name }}</span>
                                @endif
                                @if ($schedule->classroom)
                                    <span class="block truncate">{{ $schedule->classroom->studentHint() }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
