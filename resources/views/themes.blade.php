<x-filament-panels::page>
    @php
        $lib = $this->library();
        $themes = $this->themes();
        $core = class_exists('Simplify\Core\SimplifyPlugin');
        $t = fn (string $key, array $replace = []) => __('simplify-themes::themes.'.$key, $replace);
        $roles = \Simplify\Themes\Support\Theme::ROLES;
        $pelican = ['accent' => 'light', 'gray' => null, 'ok' => 'light', 'warn' => 'light', 'danger' => 'light'];
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

    <form wire:submit="save" data-simplify-unsaved="data" class="st s-fit {{ $core ? 'st-core' : 'st-pelican' }}"
        x-data="simplifyThemes(@js($lib), @js($core))" x-on:simplify-themes-opened.window="$nextTick(() => remember())">
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
                                @php($p = $theme['data']['light'])
                                <button type="button" class="st-tile" wire:key="tile-{{ $theme['id'] }}" wire:click="pick({{ $theme['id'] }})" aria-pressed="{{ $theme['id'] === $themeId ? 'true' : 'false' }}">
                                    <span class="st-mini" style="background: {{ $p['bg'] }}">
                                        <i style="background: {{ $p['surface'] }}; border-color: {{ $p['line'] }}"></i>
                                        <i style="background: {{ $p['surface'] }}; border-color: {{ $p['line'] }}"><b style="background: {{ $p['accent'] }}"></b></i>
                                    </span>
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
                        <div class="st-choices">
                            @foreach (['plain', 'gradient', 'image'] as $kind)
                                <button type="button" class="st-choice" x-on:click="data.theme.bg = '{{ $kind }}'" :aria-pressed="data.theme.bg === '{{ $kind }}'"
                                    @if ($kind === 'image') :disabled="lib.images.length === 0" @endif>
                                    <b>{{ $t('bg_'.$kind) }}</b><small>{{ $t('bg_'.$kind.'_help') }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="st-sec">
                        <div class="st-sec-head"><h3>{{ $t('pictures') }}</h3>{{ $this->uploadImageAction }}</div>
                        <p class="st-hint">{{ $t('pictures_help') }}</p>
                        @forelse ($lib['images'] as $image)
                            <div class="st-file" wire:key="image-{{ $image['id'] }}">
                                <span class="st-thumb" style="background-image: url('{{ $image['url'] }}')"></span>
                                <span class="st-file-meta">{{ $image['name'] }}<small>{{ number_format($image['size'] / 1024) }} KB</small></span>
                                <button type="button" class="st-btn" x-on:click="data.theme.image = {{ $image['id'] }}; data.theme.bg = 'image'" :aria-pressed="data.theme.image === {{ $image['id'] }}">{{ $t('use_light') }}</button>
                                @if ($core)
                                    <button type="button" class="st-btn" x-on:click="data.theme.image_dark = data.theme.image_dark === {{ $image['id'] }} ? null : {{ $image['id'] }}" :aria-pressed="data.theme.image_dark === {{ $image['id'] }}">{{ $t('use_dark') }}</button>
                                @endif
                                {{ ($this->removeFileAction)(['id' => $image['id']]) }}
                            </div>
                        @empty
                            <p class="st-empty">{{ $t('pictures_none') }}</p>
                        @endforelse
                    </div>
                    <div class="st-sec" x-show="data.theme.bg === 'image'">
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
                        <div class="st-sec" x-show="data.theme.bg !== 'plain'">
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
                                <span class="st-file-meta">{{ $font['label'] }}<small>{{ number_format($font['size'] / 1024) }} KB</small></span>
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
                    <div class="st-pv-bg" x-show="data.theme.bg === 'image' && pictureUrl()" :style="{ backgroundImage: 'linear-gradient(rgb(0 0 0 / ' + (data.theme.dim / 100) + '), rgb(0 0 0 / ' + (data.theme.dim / 100) + ')), url(' + pictureUrl() + ')', filter: 'blur(' + data.theme.blur + 'px)', backgroundSize: data.theme.fit === 'fit' ? 'contain' : (data.theme.fit === 'tile' ? 'auto' : 'cover'), backgroundRepeat: data.theme.fit === 'tile' ? 'repeat' : 'no-repeat' }"></div>
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
        Alpine.data('simplifyThemes', (lib, core) => ({
            lib,
            core,
            data: $wire.$entangle('data'),
            tab: 'themes',
            mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
            sheet: false,
            base: '',
            init() { this.remember(); },
            remember() { this.base = JSON.stringify(this.data); },
            changed() { return this.base !== '' && JSON.stringify(this.data) !== this.base; },
            hex(v) { return /^#[0-9a-f]{6}$/i.test(v || '') ? v.toLowerCase() : '#888888'; },
            rgb(h) { h = this.hex(h); return [1, 3, 5].map(i => parseInt(h.slice(i, i + 2), 16)); },
            mix(a, b, w) { const x = this.rgb(a), y = this.rgb(b); return '#' + x.map((v, i) => Math.round(v * (1 - w) + y[i] * w).toString(16).padStart(2, '0')).join(''); },
            lum(h) { return this.rgb(h).map(v => { v /= 255; return v <= .03928 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; }).reduce((s, v, i) => s + v * [.2126, .7152, .0722][i], 0); },
            ratio(a, b) { const [x, y] = [this.lum(a), this.lum(b)].sort((p, q) => q - p); return (x + .05) / (y + .05); },
            on(c) { return this.ratio(c, '#ffffff') >= this.ratio(c, '#111118') ? '#ffffff' : '#111118'; },
            font(key, generic) { const f = [...lib.fonts, ...lib.uploaded].find(x => x.key === key); return f ? f.stack : 'inherit'; },
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
            pictureUrl() {
                const t = this.data.theme;
                const id = this.mode === 'dark' && t.image_dark ? t.image_dark : t.image;
                const image = lib.images.find(i => i.id === id);
                return image ? image.url : '';
            },
            vars() {
                const t = this.data.theme, p = this.palette();
                const glass = core && t.bg !== 'plain' ? t.glass : 0;
                const shadow = { none: 'none', soft: '0 4px 14px rgb(0 0 0 / .12)', strong: '0 10px 28px rgb(0 0 0 / .28)' }[core ? t.shadow : 'soft'];
                const line = !core ? p.line : t.lines === 'subtle' ? this.mix(p.line, p.surface, .45) : t.lines === 'strong' ? this.mix(p.line, p.ink, .18) : p.line;
                const follow = core && t.console === 'follow';
                return {
                    '--pv-bg': t.bg === 'gradient' ? `linear-gradient(160deg, ${p.bg}, ${this.mix(p.bg, p.accent, .22)})` : p.bg,
                    '--pv-surface': glass ? `color-mix(in srgb, ${p.surface} ${100 - glass}%, transparent)` : p.surface,
                    '--pv-raised': p.raised, '--pv-ink': p.ink, '--pv-muted': p.muted, '--pv-line': line,
                    '--pv-accent': p.accent, '--pv-on': this.on(p.accent), '--pv-ok': p.ok, '--pv-warn': p.warn, '--pv-danger': p.danger,
                    '--pv-r': (core ? t.radius : 12) + 'px', '--pv-d': core ? { compact: .85, normal: 1, roomy: 1.1 }[t.density] : 1, '--pv-shadow': shadow,
                    '--pv-font': t.font === 'inter' ? 'inherit' : this.font(t.font), '--pv-head': t.head === 'inter' ? 'inherit' : this.font(t.head), '--pv-mono': this.font(t.mono),
                    '--pv-con-bg': follow ? p.surface : '#09090b', '--pv-con-ink': follow ? p.ink : '#e4e4e7', '--pv-con-line': follow ? line : '#2c2c33',
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
