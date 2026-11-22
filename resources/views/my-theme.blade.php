<x-filament-panels::page>
    @php($themes = collect(\Simplify\Themes\Support\Store::themes())->keyBy('id'))
    <form wire:submit="save" data-simplify-unsaved="choice" class="st-mine s-fit">
        @foreach ($this->options() as $value => $label)
            @php($p = $themes[(int) $value]['data']['light'] ?? null)
            <label class="st-mine-row">
                <input type="radio" wire:model="choice" value="{{ $value }}">
                <span class="st-mine-swatch" @if ($p) style="background: {{ $p['bg'] }}; border-color: {{ $p['line'] }}" @endif>
                    @if ($p)<i style="background: {{ $p['surface'] }}"></i><b style="background: {{ $p['accent'] }}"></b>@endif
                </span>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </form>
    <style>
        .st-mine { display: grid; gap: 4px; max-width: 560px; }
        .st-mine-row { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; cursor: pointer; }
        .st-mine-row:hover { background: color-mix(in srgb, currentColor 6%, transparent); }
        .st-mine-swatch { position: relative; width: 54px; height: 34px; border-radius: 8px; border: 1px solid color-mix(in srgb, currentColor 20%, transparent); background: linear-gradient(135deg, #f4f4f5 50%, #101012 50%); overflow: hidden; flex: none; }
        .st-mine-swatch i { position: absolute; inset: 8px 8px 8px 6px; border-radius: 4px; }
        .st-mine-swatch b { position: absolute; right: 10px; bottom: 11px; width: 14px; height: 5px; border-radius: 3px; }
    </style>
</x-filament-panels::page>
