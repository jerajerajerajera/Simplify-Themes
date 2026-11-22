{{-- The Themes page. Colours come from Simplify's tokens, or Filament's own without Simplify; nothing here is themed by
     the theme being edited except the preview (.st-pv, --pv-*). --}}
<style>
    .st { --st-surface: var(--ps-surface, #fff); --st-raised: var(--ps-raised, #f4f4f5); --st-field: var(--ps-field, #fff); --st-ink: var(--ps-ink, #18181b);
        --st-muted: var(--ps-muted, #71717a); --st-line: var(--ps-line, #e4e4e7); --st-warn: var(--ps-warn, #d97706); --st-ok: var(--ps-ok, #16a34a);
        --st-primary: var(--primary-600, #2563eb); --st-r: var(--ps-radius, 8px);
        display: flex; flex-direction: column; min-height: 0; color: var(--st-ink); }
    .dark .st { --st-surface: var(--ps-surface, #18181b); --st-raised: var(--ps-raised, #27272a); --st-field: var(--ps-field, #18181b); --st-ink: var(--ps-ink, #fafafa);
        --st-muted: var(--ps-muted, #a1a1aa); --st-line: var(--ps-line, #ffffff1a); --st-warn: var(--ps-warn, #fbbf24); --st-ok: var(--ps-ok, #4ade80); --st-primary: var(--primary-500, #3b82f6); }
    [x-cloak] { display: none !important; }
    .st-i { width: 16px; height: 16px; flex: none; }
    .st-bar { display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--st-line); }
    .st-tabs { display: flex; gap: 2px; overflow-x: auto; scrollbar-width: none; flex: 1; min-width: 0; }
    .st-tab { display: inline-flex; align-items: center; gap: 7px; padding: 8px 12px 10px; border: 0; border-bottom: 2px solid transparent; background: none; color: var(--st-muted); font-weight: 600; white-space: nowrap; cursor: pointer; }
    .st-tab[aria-selected="true"] { color: var(--st-ink); border-bottom-color: var(--st-primary); }
    .st-split { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 0; min-height: 0; }
    .st-edit { min-width: 0; padding-bottom: 80px; }
    .st-sec { padding: 16px 20px 8px 0; }
    .st-sec + .st-sec { border-top: 1px solid var(--st-line); }
    .st-sec h3 { margin: 0 0 2px; font-size: 15px; font-weight: 600; }
    .st-sec-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .st-hint { margin: 0 0 10px; color: var(--st-muted); font-size: 13px; }
    .st-empty { color: var(--st-muted); font-size: 13px; margin: 6px 0; }
    .st-error { color: var(--ps-danger, #dc2626); font-size: 12px; margin: 4px 0 0; }
    .st-colhead, .st-row { display: grid; grid-template-columns: 160px 1fr 1fr; gap: 10px; align-items: center; }
    .st-colhead { font-size: 12px; font-weight: 600; color: var(--st-muted); padding-bottom: 4px; }
    .st-row { padding: 7px 0; border-top: 1px solid var(--st-line); }
    .st-row-one { grid-template-columns: 160px minmax(0, 220px); }
    .st-lbl { font-weight: 500; }
    .st-lbl small, .st-field small { display: block; color: var(--st-muted); font-weight: 400; font-size: 12px; }
    .st-swatch { display: flex; align-items: center; gap: 8px; height: 36px; padding: 0 8px 0 4px; border: 1px solid var(--st-line); border-radius: var(--st-r); background: var(--st-field); }
    .st-swatch.is-dim { opacity: .6; }
    .st-swatch input[type=color] { width: 28px; height: 28px; padding: 0; border: 0; border-radius: 6px; background: none; cursor: pointer; }
    .st-swatch input[type=text] { width: 100%; min-width: 0; border: 0; background: none; font: 12px ui-monospace, Menlo, Consolas, monospace; color: inherit; }
    .st-pills, .st-actions { display: flex; flex-wrap: wrap; gap: 6px; }
    .st-actions { margin-top: 12px; }
    .st-pill { padding: 2px 9px; border-radius: 99px; font-size: 12px; font-weight: 600; }
    .st-pill.is-ok { color: var(--st-ok); background: color-mix(in srgb, var(--st-ok) 13%, transparent); }
    .st-pill.is-warn { color: var(--st-warn); background: color-mix(in srgb, var(--st-warn) 14%, transparent); }
    .st-btn { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 12px; border: 1px solid var(--st-line); border-radius: var(--st-r); background: var(--st-surface); color: inherit; font-weight: 600; font-size: 13px; white-space: nowrap; cursor: pointer; }
    .st-btn:hover, .st-btn[aria-pressed="true"] { background: var(--st-raised); }
    .st-btn[aria-pressed="true"] { border-color: var(--st-primary); }
    .st-btn.is-primary { background: var(--st-primary); border-color: var(--st-primary); color: #fff; }
    .st-peek, .st-close { display: none; }
    .st-choices { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; width: 100%; }
    .st-choice { display: flex; flex-direction: column; gap: 3px; padding: 10px 12px; border: 1px solid var(--st-line); border-radius: var(--st-r); background: var(--st-surface); color: inherit; text-align: left; cursor: pointer; }
    .st-choice[aria-pressed="true"] { border-color: var(--st-primary); box-shadow: 0 0 0 1px var(--st-primary); }
    .st-choice:disabled { opacity: .5; cursor: default; }
    .st-choice b { font-size: 13px; }
    .st-choice small { color: var(--st-muted); font-size: 12px; }
    .st-sample { font-size: 21px; line-height: 1.15; }
    .st-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
    .st-tile { display: flex; flex-direction: column; align-items: stretch; width: 100%; padding: 0; overflow: hidden; border: 1px solid var(--st-line); border-radius: var(--st-r); background: var(--st-surface); color: inherit; text-align: left; cursor: pointer; }
    .st-tile[aria-pressed="true"] { border-color: var(--st-primary); box-shadow: 0 0 0 1px var(--st-primary); }
    .st-mini { box-sizing: border-box; width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; height: 74px; padding: 10px; align-content: end; }
    .st-mini i { position: relative; height: 28px; border: 1px solid; border-radius: 6px; }
    .st-mini b { position: absolute; right: 6px; bottom: 6px; width: 20px; height: 7px; border-radius: 4px; }
    .st-tile-info { box-sizing: border-box; width: 100%; padding: 8px 12px; border-top: 1px solid var(--st-line); }
    .st-tile-info b { display: block; font-size: 13px; }
    .st-tile-info small { display: block; min-height: 1.3em; color: var(--st-muted); font-size: 12px; }
    .st-field { display: grid; grid-template-columns: 160px minmax(0, 1fr); align-items: center; gap: 10px; padding: 9px 0; }
    .st-field + .st-field { border-top: 1px solid var(--st-line); }
    .st-input { width: 100%; max-width: 360px; height: 36px; padding: 0 10px; border: 1px solid var(--st-line); border-radius: var(--st-r); background: var(--st-field); color: inherit; }
    .st-switch { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; }
    .st-switch input { position: absolute; opacity: 0; }
    .st-switch i { position: relative; width: 40px; height: 22px; flex: none; border-radius: 99px; background: var(--st-line); transition: background .15s; }
    .st-switch i::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: transform .15s; }
    .st-switch input:checked + i { background: var(--st-primary); }
    .st-switch input:checked + i::after { transform: translateX(18px); }
    .st-switch input:focus-visible + i { outline: 2px solid var(--st-primary); outline-offset: 2px; }
    .st-switch .st-hint { margin: 0; }
    .st-range { display: flex; align-items: center; gap: 10px; max-width: 420px; }
    .st-range input { flex: 1; accent-color: var(--st-primary); }
    .st-range output { width: 44px; text-align: right; color: var(--st-muted); font-size: 12px; font-variant-numeric: tabular-nums; }
    .st-seg { display: inline-flex; flex-wrap: wrap; gap: 2px; padding: 3px; border-radius: var(--st-r); background: var(--st-raised); justify-self: start; }
    .st-seg button { display: inline-flex; align-items: center; padding: 5px 11px; border: 0; border-radius: 6px; background: none; color: var(--st-muted); font-size: 13px; font-weight: 600; cursor: pointer; }
    .st-seg button[aria-pressed="true"] { background: var(--st-surface); color: var(--st-ink); box-shadow: 0 1px 2px rgb(0 0 0 / .12); }
    .st-file { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-top: 1px solid var(--st-line); }
    .st-thumb { width: 44px; height: 32px; flex: none; border-radius: 6px; background: var(--st-raised) center / cover; }
    .st-aa { display: grid; place-items: center; font-size: 15px; font-weight: 700; }
    .st-file-meta { margin-right: auto; min-width: 0; overflow-wrap: anywhere; }
    .st-file-meta small { display: block; color: var(--st-muted); font-size: 12px; }
    .st-note { display: flex; gap: 8px; align-items: center; color: var(--st-muted); font-size: 13px; padding: 12px 0; }
    .st-side { position: sticky; top: 0; align-self: start; display: flex; flex-direction: column; gap: 10px; height: min(560px, calc(var(--s-fit, 100vh) - 60px)); padding: 14px 0 14px 16px; border-left: 1px solid var(--st-line); }
    .st-side-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .st-scrim { display: none; }
    .st-unsaved { position: fixed; left: 50%; bottom: max(20px, env(safe-area-inset-bottom)); z-index: 40; display: flex; align-items: center; gap: 10px; padding: 8px 8px 8px 14px; transform: translateX(-50%);
        border: 1px solid color-mix(in srgb, var(--st-warn) 55%, transparent); border-radius: 14px; background: var(--st-surface); box-shadow: 0 14px 36px rgb(0 0 0 / .18); white-space: nowrap; }
    .st-unsaved > .st-i { color: var(--st-warn); }

    /* Preview */
    .st-pv { position: relative; flex: 1; min-height: 0; overflow: hidden; border: 1px solid var(--st-line); border-radius: 12px; background: var(--pv-bg); color: var(--pv-ink); font-family: var(--pv-font); font-size: 13px; }
    .st-pv-bg { position: absolute; inset: -20px; background-position: center; pointer-events: none; }
    .st-pv-fx { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; display: block; }
    .st-pv-pel { position: relative; }
    .st-fxs { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }
    .st-fxs .st-choice { padding: 8px; gap: 6px; }
    .st-fx-thumb { display: block; width: 100%; height: 40px; border-radius: 6px; background: var(--st-raised, rgb(127 127 127 / .12)); }
    .st-dirbox { display: flex; align-items: center; gap: 14px; }
    .st-dirbox output { color: var(--st-muted); font-size: 12px; font-variant-numeric: tabular-nums; }
    .st-dial { width: 84px; height: 84px; flex: none; border-radius: 50%; cursor: grab; touch-action: none; }
    .st-dial:focus-visible { outline: 2px solid var(--st-primary); outline-offset: 3px; }
    .st-dial svg { display: block; width: 100%; height: 100%; }
    .st-dial-ring { fill: var(--st-raised, transparent); stroke: var(--st-line); stroke-width: 1.5; }
    .st-dial-ticks { fill: none; stroke: var(--st-muted); stroke-width: 1.5; }
    .st-dial-hand { transform-origin: 50px 50px; }
    .st-dial-hand path:first-child { fill: none; stroke: var(--st-primary); stroke-width: 3.5; stroke-linecap: round; }
    .st-dial-tip { fill: var(--st-primary); }
    .st-fx-colour { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .st-fx-colour input[type=color] { width: 40px; height: 30px; padding: 2px; border: 1px solid var(--st-line); border-radius: 8px; background: var(--st-surface); cursor: pointer; }
    .st-fx-colour input[type=color]:disabled { opacity: .4; cursor: default; }
    .st-pv-in { position: relative; display: flex; flex-direction: column; gap: calc(10px * var(--pv-d)); padding: calc(12px * var(--pv-d)); }
    .st-pv-top { display: flex; align-items: center; gap: 8px; }
    .st-pv-name { margin-right: auto; font-family: var(--pv-head); font-size: 17px; font-weight: 700; }
    .st-pv-icon { display: grid; place-items: center; width: 28px; height: 28px; border: 1px solid var(--pv-line); border-radius: calc(var(--pv-r) * .6); background: var(--pv-surface); }
    .st-pv-btn { padding: 6px 10px; border-radius: calc(var(--pv-r) * .6); background: var(--pv-accent); color: var(--pv-on); font-size: 12px; font-weight: 600; }
    .st-pv-chips { display: flex; gap: 6px; }
    .st-pv-chips span { padding: 3px 10px; border: 1px solid var(--pv-line); border-radius: 99px; background: var(--pv-surface); color: var(--pv-muted); font-size: 11px; font-weight: 600; }
    .st-pv-chips .is-on { border-color: transparent; background: var(--pv-ink); color: var(--pv-surface); }
    .st-pv-card { padding: calc(11px * var(--pv-d)); border: 1px solid var(--pv-line); border-radius: var(--pv-r); background: var(--pv-surface); box-shadow: var(--pv-shadow); }
    .st-pv-r { display: flex; align-items: center; gap: 8px; }
    .st-pv-r b { margin-right: auto; font-family: var(--pv-head); font-size: 14px; }
    .st-pv-ico { width: 28px; height: 28px; flex: none; border-radius: calc(var(--pv-r) * .5); background: linear-gradient(#5fa83a 0 38%, #8b5a2b 38%); }
    .st-pv-ico.is-grey { background: linear-gradient(#9aa4ad 0 40%, #5d6770 40%); }
    .st-pv-st { padding: 2px 7px; border-radius: 99px; font-size: 10px; font-weight: 700; }
    .st-pv-st.is-ok { color: var(--pv-ok); background: color-mix(in srgb, var(--pv-ok) 14%, transparent); }
    .st-pv-st.is-warn { color: var(--pv-warn); background: color-mix(in srgb, var(--pv-warn) 16%, transparent); }
    .st-pv-st.is-no { color: var(--pv-danger); background: color-mix(in srgb, var(--pv-danger) 14%, transparent); }
    .st-pv-meters { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 8px; }
    .st-pv-meters > span { padding: 6px 8px; border-radius: calc(var(--pv-r) * .5); background: var(--pv-raised); color: var(--pv-muted); font-size: 11px; }
    .st-pv-meters em { float: right; color: var(--pv-ok); font-family: var(--pv-mono); font-style: normal; font-weight: 600; }
    .st-pv-meters i { display: block; height: 4px; margin-top: 5px; overflow: hidden; border-radius: 4px; background: var(--pv-line); }
    .st-pv-meters i b { display: block; height: 100%; background: var(--pv-accent); }
    .st-pv-players { margin-top: 6px; color: var(--pv-ok); font-family: var(--pv-mono); font-size: 12px; font-weight: 600; }
    .st-pv-con { padding: 9px 10px; border: 1px solid var(--pv-con-line); border-radius: var(--pv-r); background: var(--pv-con-bg); color: var(--pv-con-ink); font: 11px/1.55 var(--pv-mono); }
    .st-pv-con .is-warn { color: #e5b450; }
    .st-pv-con .is-ok { color: #86cf9d; }
    .st-pv-pel-top { position: relative; display: flex; align-items: center; gap: 7px; height: 34px; padding: 0 10px; border-bottom: 1px solid var(--pv-line); background: var(--pv-surface); font-weight: 700; font-size: 12px; }
    .st-pv-pel-top i { width: 13px; height: 13px; border-radius: 4px; background: var(--pv-accent); transform: rotate(45deg) scale(.8); }
    .st-pv-table { overflow: hidden; border: 1px solid var(--pv-line); border-radius: 10px; background: var(--pv-surface); font-size: 11px; }
    .st-pv-table > div { display: flex; justify-content: space-between; padding: 7px 9px; border-top: 1px solid var(--pv-line); }
    .st-pv-table > div:first-child { border-top: 0; background: var(--pv-raised); color: var(--pv-muted); font-weight: 600; }

    /* Without Simplify the page looks like Pelican's own: sections are cards on the grey page. */
    .st-pelican .st-bar { margin-bottom: 16px; }
    .st-pelican .st-edit { padding-right: 20px; }
    .st-pelican .st-sec { margin-bottom: 16px; padding: 16px 20px; border: 1px solid var(--st-line); border-radius: 12px; background: var(--st-surface); }
    .st-pelican .st-sec + .st-sec { border-top: 1px solid var(--st-line); }
    .st-pelican .st-side { padding: 14px; border: 1px solid var(--st-line); border-radius: 12px; background: var(--st-surface); }

    /* Phone and narrow windows: the preview is a sheet from below, opened with "Live preview". */
    @media (max-width: 900px) {
        .st-split { grid-template-columns: minmax(0, 1fr); }
        .st-sec { padding-right: 0; }
        .st-pelican .st-edit { padding-right: 0; }
        .st-peek { display: inline-flex; }
        .st-close { display: inline-flex; }
        .st-side { position: fixed; inset: auto 0 0 0; top: 90px; z-index: 40; height: auto; padding: 10px 14px 18px; border: 0; border-radius: 20px 20px 0 0; background: var(--st-surface); box-shadow: 0 -10px 40px rgb(0 0 0 / .25); transform: translateY(105%); visibility: hidden; pointer-events: none; transition: transform .2s, visibility 0s .2s; }
        .st-side.is-open { transform: none; visibility: visible; pointer-events: auto; transition: transform .2s; }
        .st-scrim { position: fixed; inset: 0; z-index: 39; display: block; background: rgb(0 0 0 / .35); }
        .st-colhead, .st-row { grid-template-columns: 96px 1fr 1fr; gap: 6px; }
        .st-row-one { grid-template-columns: 110px minmax(0, 1fr); }
        .st-field { grid-template-columns: minmax(0, 1fr); gap: 6px; }
        .st-choices, .st-tiles { grid-template-columns: 1fr 1fr; }
    }
    @media (prefers-reduced-motion: reduce) { .st-side, .st-switch i, .st-switch i::after { transition: none; } }
</style>
