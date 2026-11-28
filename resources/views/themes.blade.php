<x-filament-panels::page>
    @php
        $lib = $this->library();
        $themes = $this->themes();
        $core = class_exists('Simplify\Core\SimplifyPlugin');
        $t = fn (string $key, array $replace = []) => __('simplify-themes::themes.'.$key, $replace);
        $roles = \Simplify\Themes\Support\Theme::ROLES;
        $pelican = ['accent' => 'light', 'gray' => null, 'ok' => 'light', 'warn' => 'light', 'danger' => 'light'];
        $effects = \Simplify\Themes\Support\Theme::CHOICES['effect'];
        $tabs = ['themes' => 'tabler-stack-2', 'colours' => 'tabler-palette', 'background' => 'tabler-photo', 'fonts' => 'tabler-typography'] + ($core ? ['shapes' => 'tabler-square-rounded'] : []);
    @endphp

    <style>
        @foreach ([...$lib['fonts'], ...$lib['uploaded']] as $font)
            @foreach ($font['faces'] as $face)
                @font-face { font-family: "{{ $face['family'] }}"; src: url("{{ $face['url'] }}") format("{{ $face['format'] }}"); font-weight: {{ $face['weight'] }}; font-display: swap; }
            @endforeach
        @endforeach
    </style>
    @include('simplify-themes::partials.styles')
    <script src="{{ \Simplify\Themes\Support\Effects::scriptUrl() }}" data-navigate-once></script>

    <form wire:submit="save" data-simplify-unsaved="data" class="st s-fit {{ $core ? 'st-core' : 'st-pelican' }}"
        x-data="simplifyThemes(@js($core))" x-on:simplify-themes-opened.window="$nextTick(() => remember())" x-on:simplify-themes-uploaded.window="uploaded($event.detail)">
        <script type="application/json" class="st-lib">@json($lib)</script>
        <div class="st-bar">
            <div class="st-tabs" role="tablist">
                @foreach ($tabs as $key => $icon)
                    <button type="button" role="tab" class="st-tab" x-on:click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'">
                        <x-filament::icon :icon="$icon" class="st-i" />{{ $t('tab_'.$key) }}
                    </button>
                @endforeach
            </div>
            <button type="button" class="st-btn st-peek" x-on:click="sheet = true"><x-filament::icon icon="tabler-eye" class="st-i" />{{ $t('preview') }}</button>
        </div>

        <div class="st-split">
            <div class="st-edit">
                {{-- Themes --}}
                <section x-show="tab === 'themes'" x-cloak>
                    <div class="st-sec">
                        <h3>{{ $t('themes') }}</h3>
                        <p class="st-hint">{{ $t('themes_help') }}</p>
                        <div class="st-tiles">
                            @foreach ($themes as $theme)
                                <button type="button" class="st-tile" wire:key="tile-{{ $theme['id'] }}" wire:click="pick({{ $theme['id'] }})" aria-pressed="{{ $theme['id'] === $themeId ? 'true' : 'false' }}">
                                    {{-- The panel's own mode: light tiles in light mode, dark tiles in dark mode. --}}
                                    @foreach (['light', 'dark'] as $tileMode)
                                        @php($p = $theme['data'][$tileMode])
                                        <span class="st-mini is-{{ $tileMode }}" style="background: {{ $p['bg'] }}">
                                            <i style="background: {{ $p['surface'] }}; border-color: {{ $p['line'] }}"></i>
                                            <i style="background: {{ $p['surface'] }}; border-color: {{ $p['line'] }}"><b style="background: {{ $p['accent'] }}"></b></i>
                                        </span>
                                    @endforeach
                                    <span class="st-tile-info"><b>{{ $theme['name'] }}</b><small>{{ $theme['default'] ? $t('default_for_everyone') : '' }}</small></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="st-sec">
                        <h3>{{ $t('this_theme') }}</h3>
                        <div class="st-field">
                            <label for="st-name">{{ $t('name') }}</label>
                            <div>
                                <input id="st-name" type="text" class="st-input" maxlength="40" x-model="data.name">
                                @error('data.name')<p class="st-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="st-field">
                            <span>{{ $t('let_people_pick') }}</span>
                            <label class="st-switch"><input type="checkbox" x-model="data.pick"><i></i><span class="st-hint">{{ $t('let_people_pick_help') }}</span></label>
                        </div>
                        @if (\Simplify\Themes\Support\StatusPage::supported())
                            <div class="st-field">
                                <span>{{ $t('status_page') }}</span>
                                <label class="st-switch"><input type="checkbox" x-model="data.status"><i></i><span class="st-hint">{{ $t('status_page_help') }}</span></label>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Colours --}}
                <section x-show="tab === 'colours'" x-cloak>
                    <div class="st-sec">
                        <h3>{{ $t('tab_colours') }}</h3>
                        @if ($core)
                            <p class="st-hint">{{ $t('colours_help') }}</p>
                            <div class="st-colhead"><span></span><span>{{ $t('light') }}</span><span>{{ $t('dark') }}</span></div>
                            @foreach ($roles as $role)
                                <div class="st-row">
                                    <span class="st-lbl">{{ $t('role_'.$role) }}<small>{{ $t('role_'.$role.'_help') }}</small></span>
                                    @foreach (['light', 'dark'] as $mode)
                                        <label class="st-swatch" :class="{ 'is-dim': mode !== '{{ $mode }}' }">
                                            <input type="color" x-model="data.theme.{{ $mode }}.{{ $role }}" aria-label="{{ $t('role_'.$role) }} {{ $t($mode) }}">
                                            <input type="text" maxlength="7" x-model.lazy="data.theme.{{ $mode }}.{{ $role }}" aria-label="{{ $t('role_'.$role) }} {{ $t($mode) }}">
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <p class="st-hint">{{ $t('colours_help_pelican') }}</p>
                            @foreach ($pelican as $role => $mode)
                                @php($path = $mode ? "data.theme.light.$role" : 'data.theme.gray')
                                <div class="st-row st-row-one">
                                    <span class="st-lbl">{{ $t($role === 'gray' ? 'role_gray' : 'role_'.$role) }}<small>{{ $t($role === 'gray' ? 'role_gray_help' : 'role_'.$role.'_help') }}</small></span>
                                    <label class="st-swatch">
                                        <input type="color" x-model="{{ $path }}" aria-label="{{ $t($role === 'gray' ? 'role_gray' : 'role_'.$role) }}">
                                        <input type="text" maxlength="7" x-model.lazy="{{ $path }}">
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div class="st-sec">
                        <h3>{{ $t('readability') }}</h3>
                        <p class="st-hint">{{ $t('readability_help') }}</p>
                        <div class="st-pills">
                            <template x-for="pair in readability()" :key="pair.key">
                                <span class="st-pill" :class="pair.ratio >= 4.5 ? 'is-ok' : 'is-warn'" x-text="pair.label + ' ' + pair.ratio.toFixed(1) + ':1'"></span>
                            </template>
                        </div>
                        @if ($core)
                            <div class="st-actions">
                                <button type="button" class="st-btn" x-on:click="darkFromLight()"><x-filament::icon icon="tabler-moon" class="st-i" />{{ $t('dark_from_light') }}</button>
                                <button type="button" class="st-btn" x-on:click="fromAccent()"><x-filament::icon icon="tabler-color-swatch" class="st-i" />{{ $t('from_accent') }}</button>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Background --}}
                <section x-show="tab === 'background'" x-cloak>
                    <div class="st-sec">
                        <h3>{{ $t('tab_background') }}</h3>
                        <p class="st-hint">{{ $t('background_help') }}</p>
                        {{-- Light and dark each have their own background; the preview's mode is the one being changed. --}}
                        <div class="st-seg st-bgmode" role="group">
                            @foreach (['light' => 'tabler-sun', 'dark' => 'tabler-moon'] as $bgMode => $bgIcon)
                                <button type="button" data-bgmode="{{ $bgMode }}" x-on:click="mode = '{{ $bgMode }}'" :aria-pressed="mode === '{{ $bgMode }}'"><x-filament::icon :icon="$bgIcon" class="st-i" />{{ $t($bgMode) }} · <span x-text="bgNames[data.theme.{{ $bgMode === 'dark' ? 'bg_dark' : 'bg' }}]"></span></button>
                            @endforeach
                        </div>
                        <div class="st-choices">
                            @foreach (['plain', 'gradient', 'image'] as $kind)
                                <button type="button" class="st-choice" data-bg="{{ $kind }}" x-on:click="data.theme[bgKey()] = '{{ $kind }}'" :aria-pressed="data.theme[bgKey()] === '{{ $kind }}'"
                                    @if ($kind === 'image') :disabled="lib.images.length === 0" @endif>
                                    <b>{{ $t('bg_'.$kind) }}</b><small>{{ $t('bg_'.$kind.'_help') }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="st-sec" x-show="data.theme[bgKey()] === 'gradient'">
                        <h3>{{ $t('gradient') }}</h3>
                        <p class="st-hint">{{ $t('gradient_help') }}</p>
                        <div class="st-gpre">
                            <button type="button" x-on:click="usePreset(null)" :style="{ background: `linear-gradient(90deg, ${palette().bg}, ${mix(palette().bg, palette().accent, .22)})` }" title="{{ $t('grad_from_theme') }}" aria-label="{{ $t('grad_from_theme') }}"></button>
                            <template x-for="(list, i) in gPresets" :key="i">
                                <button type="button" x-on:click="usePreset(list)" :style="{ background: 'linear-gradient(90deg, ' + list.map(([c, at]) => c + ' ' + at + '%').join(', ') + ')' }" :aria-label="@js($t('grad_ready')) + ' ' + (i + 1)"></button>
                            </template>
                        </div>
                        <div class="st-gwrap">
                            <div class="st-gbar" x-ref="gbar" :style="{ '--g': gradCss(g(), true) }" x-on:pointerdown="barDown($event)" x-on:pointermove="barMove($event)" x-on:pointerup="gdrag = null">
                                <template x-for="(s, i) in g().stops" :key="i"><span class="st-gstop" :class="{ 'is-on': i === gsel }" :data-stop="i" :style="{ left: s.p + '%', background: s.c }"></span></template>
                                <template x-for="m in mids()" :key="m.key"><span class="st-gmid" :class="{ 'is-seam': m.key === 'wrap' }" :data-mid="m.key" :style="{ left: m.left + '%' }"></span></template>
                            </div>
                        </div>
                        <template x-if="g().stops[gsel]">
                            <div>
                                <div class="st-field"><span>{{ $t('grad_colour') }}</span>
                                    <div class="st-gsel">
                                        <input type="color" :value="g().stops[gsel].c" x-on:input="g().stops[gsel].c = $event.target.value" aria-label="{{ $t('grad_colour') }}">
                                        <input type="text" class="st-input" maxlength="7" :value="g().stops[gsel].c" x-on:change="/^#[0-9a-f]{6}$/i.test($event.target.value) && (g().stops[gsel].c = $event.target.value.toLowerCase())" aria-label="{{ $t('grad_colour') }}">
                                        <button type="button" class="st-btn" x-on:click="removeStop(gsel)" :disabled="g().stops.length <= 2" aria-label="{{ $t('grad_remove') }}" title="{{ $t('grad_remove') }}"><x-filament::icon icon="tabler-x" class="st-i" /></button>
                                    </div>
                                </div>
                                <div class="st-field"><span>{{ $t('grad_position') }}</span><div class="st-range"><input type="range" min="0" max="100" x-model.number="g().stops[gsel].p" aria-label="{{ $t('grad_position') }}"><output x-text="g().stops[gsel].p + '%'"></output></div></div>
                            </div>
                        </template>
                        <div class="st-field"><span>{{ $t('grad_shape') }}</span>
                            <div class="st-choices st-gshape">
                                @foreach (\Simplify\Themes\Support\Gradient::TYPES as $type)
                                    <button type="button" class="st-choice" x-on:click="g().type = '{{ $type }}'" :aria-pressed="g().type === '{{ $type }}'">
                                        <i :style="{ background: gradCss({ ...g(), type: '{{ $type }}', repeat: false }, false) }"></i><b>{{ $t('grad_'.$type) }}</b>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="st-field" x-show="g().type !== 'radial'"><span>{{ $t('grad_angle') }}</span>
                            <div class="st-dirbox">
                                <div class="st-dial" role="slider" tabindex="0" aria-label="{{ $t('grad_angle') }}" aria-valuemin="0" aria-valuemax="359" :aria-valuenow="g().angle"
                                    x-on:pointerdown="dial($event, true, (d) => g().angle = d)" x-on:pointermove="dial($event, false, (d) => g().angle = d)" x-on:keydown="dialKey($event, g().angle, (d) => g().angle = d)">
                                    <svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="44" class="st-dial-ring"/><path d="M50 8v7M92 50h-7M50 92v-7M8 50h7" class="st-dial-ticks"/><g class="st-dial-hand" :style="{ transform: 'rotate(' + g().angle + 'deg)' }"><path d="M50 50V14"/><path d="M43 22l7-10 7 10" class="st-dial-tip"/></g><circle cx="50" cy="50" r="4" class="st-dial-tip"/></svg>
                                </div>
                                <output x-text="g().angle + '°'"></output>
                            </div>
                        </div>
                        @foreach (['cx', 'cy'] as $key)
                            <div class="st-field" x-show="g().type !== 'linear'"><span>{{ $t('grad_'.$key) }}</span><div class="st-range"><input type="range" min="0" max="100" x-model.number="g().{{ $key }}" aria-label="{{ $t('grad_'.$key) }}"><output x-text="g().{{ $key }} + '%'"></output></div></div>
                        @endforeach
                        <div class="st-field"><span>{{ $t('grad_repeat') }}</span>
                            <label class="st-switch st-repeat"><input type="checkbox" :checked="g().repeat" x-on:change="setRepeat($event.target.checked)"><i></i><span class="st-hint">{{ $t('grad_repeat_help') }}</span></label>
                        </div>
                        <div class="st-field" x-show="g().repeat"><span>{{ $t('grad_size') }}</span><div class="st-range"><input type="range" min="5" max="100" x-model.number="g().size" aria-label="{{ $t('grad_size') }}"><output x-text="g().size + '%'"></output></div></div>
                    </div>
                    <div class="st-sec">
                        <h3>{{ $t('effect') }}</h3>
                        <p class="st-hint">{{ $t('effect_help') }}</p>
                        <div class="st-choices st-fxs">
                            @foreach ($effects as $effect)
                                <button type="button" class="st-choice" x-on:click="pickEffect('{{ $effect }}')" :aria-pressed="data.theme.effect === '{{ $effect }}'">
                                    <canvas class="st-fx-thumb" data-fx="{{ $effect }}" :style="{ background: palette().bg }" aria-hidden="true"></canvas><b>{{ $t('fx_'.$effect) }}</b>
                                </button>
                            @endforeach
                        </div>
                        <div x-show="data.theme.effect !== 'none'">
                            @foreach (['amount' => [20, 250], 'speed' => [10, 250], 'size' => [50, 250], 'strength' => [10, 100], 'glow' => [0, 100]] as $key => [$min, $max])
                                <div class="st-field"><span>{{ $t('fx_'.$key) }}</span><div class="st-range"><input type="range" min="{{ $min }}" max="{{ $max }}" step="5" x-model.number="data.theme.fx_{{ $key }}" aria-label="{{ $t('fx_'.$key) }}"><output x-text="data.theme.fx_{{ $key }} + '%'"></output></div></div>
                            @endforeach
                            <div class="st-field"><span>{{ $t('fx_direction') }}<small>{{ $t('fx_direction_help') }}</small></span>
                                <div class="st-dirbox">
                                    <div class="st-dial" role="slider" tabindex="0" aria-label="{{ $t('fx_direction') }}" aria-valuemin="0" aria-valuemax="359" :aria-valuenow="data.theme.fx_dir"
                                        x-on:pointerdown="dial($event, true, (d) => data.theme.fx_dir = d)" x-on:pointermove="dial($event, false, (d) => data.theme.fx_dir = d)" x-on:keydown="dialKey($event, data.theme.fx_dir, (d) => data.theme.fx_dir = d)">
                                        <svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="44" class="st-dial-ring"/><path d="M50 8v7M92 50h-7M50 92v-7M8 50h7" class="st-dial-ticks"/><g class="st-dial-hand" :style="{ transform: 'rotate(' + data.theme.fx_dir + 'deg)' }"><path d="M50 50V14"/><path d="M43 22l7-10 7 10" class="st-dial-tip"/></g><circle cx="50" cy="50" r="4" class="st-dial-tip"/></svg>
                                    </div>
                                    <output x-text="data.theme.fx_dir + '°'"></output>
                                </div>
                            </div>
                            <div class="st-field"><span>{{ $t('fx_colour') }}</span>
                                <div class="st-fx-colour">
                                    <label class="st-switch"><input type="checkbox" :checked="data.theme.fx_colour === null" x-on:change="data.theme.fx_colour = $event.target.checked ? null : palette().accent"><i></i><span>{{ $t('fx_follow') }}</span></label>
                                    <input type="color" :value="data.theme.fx_colour || palette().accent" x-on:input="data.theme.fx_colour = $event.target.value" :disabled="data.theme.fx_colour === null" aria-label="{{ $t('fx_colour') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="st-sec">
                        <div class="st-sec-head"><h3>{{ $t('pictures') }}</h3>
                            {{-- The new picture becomes the background of the mode being changed: light, or dark (TH-B15). --}}
                            <button type="button" class="st-btn st-upload-image" x-on:click="$wire.mountAction('uploadImage', { dark: mode === 'dark' })"><x-filament::icon icon="tabler-upload" class="st-i" />{{ $t('image_upload') }}</button>
                        </div>
                        <p class="st-hint">{{ $t('pictures_help') }}</p>
                        @forelse ($lib['images'] as $image)
                            <div class="st-file" wire:key="image-{{ $image['id'] }}">
                                <span class="st-thumb" style="background-image: url('{{ $image['url'] }}')"></span>
                                <span class="st-file-meta">{{ $image['name'] }} @if ($image['used'])<span class="st-pill is-ok">{{ $t('in_use') }}</span>@endif<small>{{ number_format($image['size'] / 1024) }} KB</small></span>
                                {{-- For the mode being changed: light, or dark. --}}
                                <button type="button" class="st-btn" x-on:click="usePicture({{ $image['id'] }})" :aria-pressed="picture() === {{ $image['id'] }} && data.theme[bgKey()] === 'image'">{{ $t('use_light') }}</button>
                                {{ ($this->removeFileAction)(['id' => $image['id']]) }}
                            </div>
                        @empty
                            <p class="st-empty">{{ $t('pictures_none') }}</p>
                        @endforelse
                    </div>
                    <div class="st-sec" x-show="data.theme[bgKey()] === 'image'">
                        <div class="st-field"><span>{{ $t('dim') }}</span><div class="st-range"><input type="range" min="0" max="80" x-model.number="data.theme.dim"><output x-text="data.theme.dim + '%'"></output></div></div>
                        <div class="st-field"><span>{{ $t('blur') }}</span><div class="st-range"><input type="range" min="0" max="20" x-model.number="data.theme.blur"><output x-text="data.theme.blur + 'px'"></output></div></div>
                        <div class="st-field"><span>{{ $t('fit') }}</span>
                            <div class="st-seg">
                                @foreach (['fill', 'fit', 'tile'] as $fit)
                                    <button type="button" x-on:click="data.theme.fit = '{{ $fit }}'" :aria-pressed="data.theme.fit === '{{ $fit }}'">{{ $t('fit_'.$fit) }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if ($core)
                        <div class="st-sec" x-show="data.theme.bg !== 'plain' || data.theme.bg_dark !== 'plain'">
                            <div class="st-field"><span>{{ $t('glass') }}<small>{{ $t('glass_help') }}</small></span><div class="st-range"><input type="range" min="0" max="50" x-model.number="data.theme.glass"><output x-text="data.theme.glass + '%'"></output></div></div>
                        </div>
                    @endif
                </section>

                {{-- Fonts --}}
                <section x-show="tab === 'fonts'" x-cloak>
                    @foreach (['font' => 'text', 'head' => 'headings'] as $slot => $label)
                        <div class="st-sec">
                            <h3>{{ $t('font_'.$label) }}</h3>
                            <p class="st-hint">{{ $t('font_'.$label.'_help') }}</p>
                            <div class="st-choices">
                                <template x-for="font in [...lib.fonts, ...lib.uploaded]" :key="font.key">
                                    <button type="button" class="st-choice" x-on:click="data.theme.{{ $slot }} = font.key" :aria-pressed="data.theme.{{ $slot }} === font.key">
                                        <b x-text="font.label"></b><span class="st-sample" :style="{ fontFamily: font.stack }">Aa 12/20</span><small x-text="font.note || '{{ $t('font_yours') }}'"></small>
                                    </button>
                                </template>
                            </div>
                        </div>
                    @endforeach
                    <div class="st-sec">
                        <div class="st-sec-head"><h3>{{ $t('font_own') }}</h3>{{ $this->uploadFontAction }}</div>
                        <p class="st-hint">{{ $t('font_upload_help') }}</p>
                        @forelse ($lib['uploaded'] as $font)
                            <div class="st-file" wire:key="font-{{ $font['id'] }}">
                                <span class="st-thumb st-aa" style="font-family: {{ $font['stack'] }}">Aa</span>
                                <span class="st-file-meta">{{ $font['label'] }} @if ($font['used'])<span class="st-pill is-ok">{{ $t('in_use') }}</span>@endif<small>{{ number_format($font['size'] / 1024) }} KB</small></span>
                                {{ ($this->removeFileAction)(['id' => $font['id']]) }}
                            </div>
                        @empty
                            <p class="st-empty">{{ $t('font_none') }}</p>
                        @endforelse
                    </div>
                </section>

                {{-- Shapes & console (with Simplify only: Pelican has no tokens for them) --}}
                @if ($core)
                    <section x-show="tab === 'shapes'" x-cloak>
                        <div class="st-sec">
                            <h3>{{ $t('tab_shapes') }}</h3>
                            <p class="st-hint">{{ $t('shapes_help') }}</p>
                            <div class="st-field"><span>{{ $t('corners') }}</span><div class="st-range"><input type="range" min="0" max="22" x-model.number="data.theme.radius"><output x-text="data.theme.radius + 'px'"></output></div></div>
                            @foreach (['density' => ['compact', 'normal', 'roomy'], 'shadow' => ['none', 'soft', 'strong'], 'lines' => ['subtle', 'normal', 'strong']] as $key => $options)
                                <div class="st-field"><span>{{ $t($key) }}</span>
                                    <div class="st-seg">
                                        @foreach ($options as $option)
                                            <button type="button" x-on:click="data.theme.{{ $key }} = '{{ $option }}'" :aria-pressed="data.theme.{{ $key }} === '{{ $option }}'">{{ $t($key.'_'.$option) }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="st-sec">
                            <h3>{{ $t('console') }}</h3>
                            <div class="st-field"><span>{{ $t('console_colours') }}</span>
                                <div class="st-seg">
                                    @foreach (['black', 'follow'] as $option)
                                        <button type="button" x-on:click="data.theme.console = '{{ $option }}'" :aria-pressed="data.theme.console === '{{ $option }}'">{{ $t('console_'.$option) }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="st-field"><span>{{ $t('console_font') }}</span>
                                <div class="st-choices">
                                    <template x-for="font in [...lib.fonts, ...lib.uploaded]" :key="font.key">
                                        <button type="button" class="st-choice" x-on:click="data.theme.mono = font.key" :aria-pressed="data.theme.mono === font.key">
                                            <b x-text="font.label"></b><span class="st-sample" :style="{ fontFamily: font.stack }">[INFO] Done</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>
                @else
                    <p class="st-note"><x-filament::icon icon="tabler-info-circle" class="st-i" />{{ $t('needs_simplify') }}</p>
                @endif
            </div>

            {{-- Live preview: only this box wears the theme until Save --}}
            <div class="st-scrim" x-show="sheet" x-cloak x-on:click="sheet = false"></div>
            <aside class="st-side" :class="{ 'is-open': sheet }">
                <div class="st-side-head">
                    <b>{{ $t('preview') }}</b>
                    <div class="st-seg">
                        <button type="button" x-on:click="mode = 'light'" :aria-pressed="mode === 'light'" aria-label="{{ $t('light') }}"><x-filament::icon icon="tabler-sun" class="st-i" /></button>
                        <button type="button" x-on:click="mode = 'dark'" :aria-pressed="mode === 'dark'" aria-label="{{ $t('dark') }}"><x-filament::icon icon="tabler-moon" class="st-i" /></button>
                    </div>
                    <button type="button" class="st-btn st-close" x-on:click="sheet = false" aria-label="{{ $t('close') }}"><x-filament::icon icon="tabler-x" class="st-i" /></button>
                </div>
                <div class="st-pv" :style="vars()">
                    <div class="st-pv-bg" x-show="data.theme[bgKey()] === 'image' && pictureUrl()" :style="{ backgroundImage: 'linear-gradient(' + veil() + ', ' + veil() + '), url(' + pictureUrl() + ')', filter: 'blur(' + data.theme.blur + 'px)', backgroundSize: data.theme.fit === 'fit' ? 'contain' : (data.theme.fit === 'tile' ? 'auto' : 'cover'), backgroundRepeat: data.theme.fit === 'tile' ? 'repeat' : 'no-repeat' }"></div>
                    <canvas class="st-pv-fx" aria-hidden="true"></canvas>
                    @if ($core)
                        <div class="st-pv-in">
                            <div class="st-pv-top"><span class="st-pv-name">{{ filament()->getBrandName() }}</span><span class="st-pv-icon"><x-filament::icon icon="tabler-bell" class="st-i" /></span><span class="st-pv-btn">{{ $t('pv_create') }}</span></div>
                            <div class="st-pv-chips"><span class="is-on">{{ $t('pv_all') }}</span><span>Minecraft</span><span>Valheim</span></div>
                            <div class="st-pv-card">
                                <div class="st-pv-r"><span class="st-pv-ico"></span><b>All the Mods 10</b><span class="st-pv-st is-ok">{{ $t('pv_running') }}</span></div>
                                <div class="st-pv-meters"><span>CPU <em>64%</em><i><b style="width: 64%"></b></i></span><span>RAM <em>6.6 GB</em><i><b style="width: 48%"></b></i></span></div>
                                <div class="st-pv-players">12 / 20</div>
                            </div>
                            <div class="st-pv-card">
                                <div class="st-pv-r"><span class="st-pv-ico is-grey"></span><b>Valheim</b><span class="st-pv-st is-warn">{{ $t('pv_starting') }}</span></div>
                            </div>
                            <div class="st-pv-con">[12:04:11 INFO]: Done (8.214s)!<br><span class="is-warn">[12:04:15 WARN]: Can't keep up!</span><br><span class="is-ok">[12:05:02 INFO]: Kirby joined the game</span></div>
                        </div>
                    @else
                        <div class="st-pv-pel">
                            <div class="st-pv-pel-top"><i></i>{{ filament()->getBrandName() }}</div>
                            <div class="st-pv-in">
                                <div class="st-pv-top"><span class="st-pv-name">{{ $t('pv_servers') }}</span><span class="st-pv-btn">{{ $t('pv_create') }}</span></div>
                                <div class="st-pv-table">
                                    <div><span>{{ $t('name') }}</span><span>Status</span></div>
                                    <div><span>All the Mods 10</span><span class="st-pv-st is-ok">{{ $t('pv_running') }}</span></div>
                                    <div><span>Valheim</span><span class="st-pv-st is-warn">{{ $t('pv_starting') }}</span></div>
                                    <div><span>Old world</span><span class="st-pv-st is-no">{{ $t('pv_offline') }}</span></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </aside>
        </div>

        {{-- Without Simplify, the same "Changes made" bar as Simplify's own (Simplify draws it itself). --}}
        <div class="st-unsaved" x-show="!window.SimplifyUnsaved && changed()" x-cloak>
            <x-filament::icon icon="tabler-alert-circle" class="st-i" /><b>{{ $t('changes_made') }}</b>
            <button type="button" class="st-btn" x-on:click="window.location.reload()">{{ $t('discard') }}</button>
            <button type="submit" class="st-btn is-primary">{{ $t('save') }}</button>
        </div>
    </form>

    @script
    <script>
        // Which tab and mode the editor shows, and what was last saved, kept outside the component: when Livewire draws
        // the form again after an upload, the editor stays where it was and still knows what changed (TH-B13). A new
        // visit to the page starts on Themes again.
        if (window.simplifyThemesView?.id !== $wire.$id) window.simplifyThemesView = { id: $wire.$id, tab: 'themes', mode: null, base: '' };
        const stView = window.simplifyThemesView;
        Alpine.data('simplifyThemes', (core) => ({
            // The fonts and pictures come from a JSON block that Livewire updates after an upload. They are not an
            // argument of x-data: a changed x-data starts the component again, which lost the tab and the changes (TH-B4).
            libText: '',
            lib: { fonts: [], uploaded: [], images: [] },
            readLib() {
                const text = this.$root.querySelector('.st-lib')?.textContent || '';
                if (text !== this.libText) { this.libText = text; try { this.lib = JSON.parse(text); } catch (e) {} }
            },
            core,
            data: $wire.$entangle('data'),
            tab: stView.tab,
            mode: stView.mode || (document.documentElement.classList.contains('dark') ? 'dark' : 'light'),
            sheet: false,
            base: '',
            init() {
                this.readLib();
                new MutationObserver(() => this.readLib()).observe(this.$root, { childList: true, subtree: true, characterData: true });
                if (stView.base) this.base = stView.base; else this.remember();
                this.$watch('tab', (v) => { stView.tab = v; });
                this.$watch('mode', (v) => { stView.mode = v; });
                // In Simplify's Admin page the title band (breadcrumb, buttons, subtitle) stays on top while the card
                // scrolls: the tabs and the preview stay right under it, however tall it is.
                const head = document.querySelector('.fi-main .fi-header');
                if (head && window.ResizeObserver) new ResizeObserver(() => this.$root.style.setProperty('--st-top', head.offsetHeight + 'px')).observe(head);
                // The moving background: the effect tiles and the live preview share the panel's own drawing script.
                const start = () => {
                    this.$root.querySelectorAll('.st-fx-thumb').forEach((cv) => window.SimplifyThemesFx.run(cv, () => this.effect(cv.dataset.fx, true)));
                    window.SimplifyThemesFx.run(this.$root.querySelector('.st-pv-fx'), () => this.effect(this.data.theme.effect, false));
                };
                window.SimplifyThemesFx ? start() : window.addEventListener('simplify-themes-fx', start, { once: true });
            },
            fxStart: @js(\Simplify\Themes\Support\Effects::START),
            fxOwn: @js(\Simplify\Themes\Support\Effects::OWN),
            fxBands: @js(\Simplify\Themes\Support\Effects::BANDS),
            pickEffect(type) { this.data.theme.effect = type; this.data.theme.fx_dir = this.fxStart[type]; },
            // What one canvas draws: a tile shows the effect plainly, the preview with this theme's settings.
            effect(type, thumb) {
                const t = this.data.theme, p = this.palette(), dark = this.mode === 'dark' ? 1 : 0;
                const own = this.fxOwn[type] ? this.fxOwn[type][dark] : 'accent', col = t.fx_colour || (own === 'accent' ? p.accent : own);
                const bands = t.fx_colour ? [col + '55', col + '33', col + '22'] : this.fxBands[dark];
                return thumb
                    ? { type, thumb, amount: 160, speed: 100, size: 100, strength: 100, glow: 0, dir: this.fxStart[type], col, bands }
                    : { type, amount: t.fx_amount, speed: t.fx_speed, size: t.fx_size, strength: t.fx_strength, glow: t.fx_glow, dir: t.fx_dir, col, bands };
            },
            // The direction dial: drag round it, or the arrow keys in steps of 15 degrees.
            dial(e, down, set) {
                const el = e.currentTarget;
                if (down) el.setPointerCapture(e.pointerId); else if (!el.hasPointerCapture(e.pointerId)) return;
                const r = el.getBoundingClientRect(), deg = Math.atan2(e.clientX - r.left - r.width / 2, -(e.clientY - r.top - r.height / 2)) * 180 / Math.PI;
                set((Math.round(deg / 5) * 5 + 360) % 360);
            },
            dialKey(e, now, set) {
                const step = { ArrowRight: 15, ArrowUp: 15, ArrowLeft: -15, ArrowDown: -15 }[e.key];
                if (step) { e.preventDefault(); set((now + step + 360) % 360); }
            },
            // The gradient of the mode the live preview shows; Gradient.php draws the same on the panel.
            gsel: 0,
            gdrag: null,
            squeezed: null,
            gPresets: @js(\Simplify\Themes\Support\Gradient::PRESETS),
            g() { return this.data.theme.grad[this.mode]; },
            gradCss(g, bar) {
                const k = g.repeat && !bar ? g.size / 100 : 1;
                const st = g.stops.map((s, i) => ({ c: s.c, p: s.p, m: g.mids[i] ?? 50 })).sort((a, b) => a.p - b.p);
                if (g.repeat) { st[st.length - 1].m = g.wrap; st.push({ c: st[0].c, p: 100 + st[0].p, m: 50 }); }
                const list = st.map((s, i) => `${s.c} ${s.p * k}%` + (st[i + 1] ? `, ${(s.p + (st[i + 1].p - s.p) * s.m / 100) * k}%` : '')).join(', ');
                if (bar) return `linear-gradient(90deg, ${list})`;
                const r = g.repeat ? 'repeating-' : '';
                if (g.type === 'radial') return `${r}radial-gradient(circle at ${g.cx}% ${g.cy}%, ${list})`;
                if (g.type === 'conic') return `${r}conic-gradient(from ${g.angle}deg at ${g.cx}% ${g.cy}%, ${list})`;
                return `${r}linear-gradient(${g.angle}deg, ${list})`;
            },
            order() { const g = this.g(); return g.stops.map((s, i) => i).sort((a, b) => g.stops[a].p - g.stops[b].p); },
            // The diamonds: one between each pair of colours, and when it repeats one for the seam back to the first.
            mids() {
                const g = this.g(), o = this.order(), out = o.slice(0, -1).map((i, n) => ({ key: i, left: g.stops[i].p + (g.stops[o[n + 1]].p - g.stops[i].p) * (g.mids[i] ?? 50) / 100 }));
                if (g.repeat) { const a = g.stops[o[o.length - 1]].p; out.push({ key: 'wrap', left: Math.min(100, a + (100 + g.stops[o[0]].p - a) * g.wrap / 100) }); }
                return out;
            },
            gpct(e) { const r = this.$refs.gbar.getBoundingClientRect(); return Math.max(0, Math.min(100, (e.clientX - r.left) / r.width * 100)); },
            barDown(e) {
                const g = this.g(), stop = e.target.closest('[data-stop]'), mid = e.target.closest('[data-mid]');
                this.$refs.gbar.setPointerCapture(e.pointerId); e.preventDefault();
                if (mid) { this.gdrag = { mid: mid.dataset.mid === 'wrap' ? 'wrap' : +mid.dataset.mid }; return; }
                if (stop) { this.gsel = +stop.dataset.stop; this.gdrag = { stop: this.gsel }; return; }
                if (g.stops.length >= {{ \Simplify\Themes\Support\Gradient::MAX }}) return;
                g.stops.push({ c: (g.stops[this.gsel] || g.stops[0]).c, p: Math.round(this.gpct(e)) }); g.mids.push(50);
                this.gsel = g.stops.length - 1; this.gdrag = { stop: this.gsel };
            },
            barMove(e) {
                if (!this.gdrag || !this.$refs.gbar.hasPointerCapture(e.pointerId)) return;
                const g = this.g(), p = this.gpct(e), o = this.order(), d = this.gdrag;
                const between = (a, b) => Math.round(Math.max(5, Math.min(95, (p - a) / ((b - a) || 1) * 100)));
                if (d.mid === 'wrap') { g.wrap = between(g.stops[o[o.length - 1]].p, 100 + g.stops[o[0]].p); return; }
                if (d.mid !== undefined) { g.mids[d.mid] = between(g.stops[d.mid].p, g.stops[o[o.indexOf(d.mid) + 1]].p); return; }
                if (e.clientY > this.$refs.gbar.getBoundingClientRect().bottom + 46 && g.stops.length > 2) { this.removeStop(d.stop); this.gdrag = null; return; }
                g.stops[d.stop].p = Math.round(p);
            },
            removeStop(i) { const g = this.g(); if (g.stops.length > 2) { g.stops.splice(i, 1); g.mids.splice(i, 1); this.gsel = 0; } },
            setRepeat(on) {
                const g = this.g(), ps = g.stops.map((s) => s.p), key = this.mode + JSON.stringify(g.stops.map((s) => s.c));
                g.repeat = on;
                // Room for the seam: when the colours fill the whole bar, they move closer together first. Turned off
                // again before anything else changed, they go back where they were.
                if (on && 100 + Math.min(...ps) - Math.max(...ps) < 15) {
                    g.stops.forEach((s) => { s.p = Math.round(s.p * .75); });
                    this.squeezed = { key, before: ps, after: g.stops.map((s) => s.p) };
                } else if (!on && this.squeezed?.key === key && JSON.stringify(ps) === JSON.stringify(this.squeezed.after)) {
                    g.stops.forEach((s, i) => { s.p = this.squeezed.before[i]; });
                }
                if (!on) this.squeezed = null;
            },
            usePreset(list) {
                const g = this.g(), p = this.palette();
                const stops = list ? list.map(([c, at]) => ({ c, p: at })) : [{ c: p.bg, p: 0 }, { c: this.mix(p.bg, p.accent, .22), p: 100 }];
                g.stops = stops; g.mids = stops.map(() => 50); this.gsel = 0;
            },
            remember() { this.base = stView.base = JSON.stringify(this.data); },
            changed() { return this.base !== '' && JSON.stringify(this.data) !== this.base; },
            hex(v) { return /^#[0-9a-f]{6}$/i.test(v || '') ? v.toLowerCase() : '#888888'; },
            rgb(h) { h = this.hex(h); return [1, 3, 5].map(i => parseInt(h.slice(i, i + 2), 16)); },
            mix(a, b, w) { const x = this.rgb(a), y = this.rgb(b); return '#' + x.map((v, i) => Math.round(v * (1 - w) + y[i] * w).toString(16).padStart(2, '0')).join(''); },
            lum(h) { return this.rgb(h).map(v => { v /= 255; return v <= .03928 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; }).reduce((s, v, i) => s + v * [.2126, .7152, .0722][i], 0); },
            ratio(a, b) { const [x, y] = [this.lum(a), this.lum(b)].sort((p, q) => q - p); return (x + .05) / (y + .05); },
            on(c) { return this.ratio(c, '#ffffff') >= this.ratio(c, '#111118') ? '#ffffff' : '#111118'; },
            font(key, generic) { const f = [...this.lib.fonts, ...this.lib.uploaded].find(x => x.key === key); return f ? f.stack : 'inherit'; },
            palette() {
                const t = this.data.theme;
                if (core) return t[this.mode];
                // Pelican makes every grey from one colour, and a lighter accent for dark mode.
                const g = this.hex(t.gray), L = this.mode === 'light', l = t.light;
                return L
                    ? { accent: l.accent, bg: this.mix(g, '#ffffff', .96), surface: '#ffffff', raised: this.mix(g, '#ffffff', .92), line: this.mix(g, '#ffffff', .82), ink: this.mix(g, '#000000', .8), muted: g, ok: l.ok, warn: l.warn, danger: l.danger }
                    : { accent: this.mix(l.accent, '#ffffff', .25), bg: this.mix(g, '#000000', .88), surface: this.mix(g, '#000000', .8), raised: this.mix(g, '#000000', .7), line: this.mix(g, '#000000', .62), ink: this.mix(g, '#ffffff', .96), muted: this.mix(g, '#ffffff', .45), ok: this.mix(l.ok, '#ffffff', .45), warn: this.mix(l.warn, '#ffffff', .45), danger: this.mix(l.danger, '#ffffff', .45) };
            },
            readability() {
                const p = this.palette();
                return [
                    { key: 'text', label: @js(__('simplify-themes::themes.contrast_text')), ratio: this.ratio(p.ink, p.surface) },
                    { key: 'muted', label: @js(__('simplify-themes::themes.contrast_muted')), ratio: this.ratio(p.muted, p.surface) },
                    { key: 'button', label: @js(__('simplify-themes::themes.contrast_button')), ratio: this.ratio(this.on(p.accent), p.accent) },
                ];
            },
            // Fade: the light background colour over the picture in light mode, black in dark mode (as on the panel).
            veil() { const [r, g, b] = this.mode === 'light' ? this.rgb(this.data.theme.light.bg) : [0, 0, 0]; return 'rgb(' + r + ' ' + g + ' ' + b + ' / ' + (this.data.theme.dim / 100) + ')'; },
            bgNames: @js(['plain' => $t('bg_plain'), 'gradient' => $t('bg_gradient'), 'image' => $t('bg_image')]),
            bgKey() { return this.mode === 'dark' ? 'bg_dark' : 'bg'; },
            // The picture of the mode being changed: dark falls back to the light one.
            picture() { const t = this.data.theme; return this.mode === 'dark' && t.image_dark ? t.image_dark : t.image; },
            // A new font or picture is picked here, not on the server: Simplify takes what the server sends back as
            // the new starting point, and the change would not count as one (TH-B13). After that answer, once the form
            // has been drawn again: Simplify first takes the form as it is, then sees the pick as a change.
            uploaded(detail) {
                setTimeout(() => {
                    window.SimplifyUnsaved?.check();
                    if (detail.font) this.data.theme.font = detail.font;
                    if (detail.image) { this.mode = detail.dark ? 'dark' : 'light'; this.usePicture(detail.image); }
                    this.$nextTick(() => window.SimplifyUnsaved?.check());
                }, 300);
            },
            usePicture(id) {
                const t = this.data.theme;
                if (this.mode === 'dark') t.image_dark = id; else t.image = id;
                t[this.bgKey()] = 'image';
            },
            pictureUrl() {
                const id = this.picture();
                const image = this.lib.images.find(i => i.id === id);
                return image ? image.url : '';
            },
            vars() {
                const t = this.data.theme, p = this.palette();
                const glass = core && t[this.bgKey()] !== 'plain' ? t.glass : 0;
                const shadow = { none: 'none', soft: '0 4px 14px rgb(0 0 0 / .12)', strong: '0 10px 28px rgb(0 0 0 / .28)' }[core ? t.shadow : 'soft'];
                const line = !core ? p.line : t.lines === 'subtle' ? this.mix(p.line, p.surface, .45) : t.lines === 'strong' ? this.mix(p.line, p.ink, .18) : p.line;
                const follow = core && t.console === 'follow';
                return {
                    '--pv-bg': t[this.bgKey()] === 'gradient' ? this.gradCss(this.g(), false) : p.bg,
                    '--pv-surface': glass ? `color-mix(in srgb, ${p.surface} ${100 - glass}%, transparent)` : p.surface,
                    '--pv-raised': p.raised, '--pv-ink': p.ink, '--pv-muted': p.muted, '--pv-line': line,
                    '--pv-accent': p.accent, '--pv-on': this.on(p.accent), '--pv-ok': p.ok, '--pv-warn': p.warn, '--pv-danger': p.danger,
                    '--pv-r': (core ? t.radius : 12) + 'px', '--pv-d': core ? { compact: .85, normal: 1, roomy: 1.1 }[t.density] : 1, '--pv-shadow': shadow,
                    '--pv-font': t.font === 'inter' ? 'inherit' : this.font(t.font), '--pv-head': t.head === 'inter' ? 'inherit' : this.font(t.head), '--pv-mono': this.font(t.mono),
                    '--pv-con-bg': follow ? p.surface : '#131a20', '--pv-con-ink': follow ? p.ink : '#e4e4e7', '--pv-con-line': follow ? line : '#2c2c33',
                };
            },
            darkFromLight() {
                const l = this.data.theme.light, a = l.accent;
                this.data.theme.dark = { ...this.data.theme.dark, accent: this.mix(a, '#ffffff', .25), bg: this.mix(a, '#000000', .92), surface: this.mix(a, '#000000', .87), raised: this.mix(a, '#000000', .82), ink: '#ededf2', muted: '#b0b0bb', line: this.mix(a, '#000000', .76) };
                this.mode = 'dark';
            },
            fromAccent() {
                const m = this.mode, a = this.data.theme[m].accent, L = m === 'light', to = L ? '#ffffff' : '#000000';
                Object.assign(this.data.theme[m], { bg: this.mix(a, to, L ? .94 : .92), raised: this.mix(a, to, L ? .88 : .82), line: this.mix(a, to, L ? .8 : .74) });
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
