<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/**
 * The moving background: one canvas behind the panel, drawn by resources/js/effects.js. The page only gets the clean
 * settings (Theme::clean) as JSON and the colours for light and dark mode; there is no code in a theme.
 */
final class Effects
{
    /** The direction each effect starts with when it is picked, in degrees (0 up, 90 right, 180 down). */
    public const START = ['none' => 180, 'stars' => 225, 'snow' => 180, 'fireflies' => 90, 'rain' => 195, 'aurora' => 90, 'bubbles' => 0];

    public const SCRIPT = 'resources/js/effects.js';

    /** Colours that follow the theme, light then dark: "accent" is the mode's own accent. The editor uses the same table. */
    public const OWN = [
        'stars' => ['accent', '#ffffff'], 'snow' => ['#9fb2c4', '#ffffff'], 'fireflies' => ['accent', '#ffe27a'],
        'rain' => ['#6b8aa3', '#9ecbe8'], 'aurora' => ['accent', '#ffffff'], 'bubbles' => ['accent', 'accent'], 'none' => ['accent', '#ffffff'],
    ];

    /** The aurora's three soft bands, light then dark. */
    public const BANDS = [['#22c55e33', '#38bdf833', '#a78bfa2a'], ['#16a34a55', '#0ea5e955', '#8b5cf644']];

    /**
     * The effect's colours in one mode: its own colour, or ones that follow the theme (OWN).
     *
     * @param  array<string, mixed>  $t  a clean theme
     * @return array{col: string, bands: list<string>}
     */
    public static function colours(array $t, string $mode): array
    {
        if ($t['fx_colour'] !== null) {
            return ['col' => $t['fx_colour'], 'bands' => [$t['fx_colour'].'55', $t['fx_colour'].'33', $t['fx_colour'].'22']];
        }
        $dark = $mode === 'dark' ? 1 : 0;
        $col = self::OWN[$t['effect']][$dark];

        return ['col' => $col === 'accent' ? $t[$mode]['accent'] : $col, 'bands' => self::BANDS[$dark]];
    }

    /** What the editor and the panel's canvas read. @param array<string, mixed> $t a clean theme @return array<string, mixed> */
    public static function settings(array $t): array
    {
        return [
            'type' => $t['effect'], 'amount' => $t['fx_amount'], 'speed' => $t['fx_speed'], 'size' => $t['fx_size'],
            'strength' => $t['fx_strength'], 'glow' => $t['fx_glow'], 'dir' => $t['fx_dir'],
            'light' => self::colours($t, 'light'), 'dark' => self::colours($t, 'dark'),
        ];
    }

    /** The canvas and the script for a page; nothing without an effect. @param array<string, mixed> $t a clean theme */
    public static function tag(array $t, string $scriptUrl): string
    {
        if ($t['effect'] === 'none') {
            return '';
        }
        $json = json_encode(self::settings($t), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR);

        $attr = fn (string $value) => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<canvas id="simplify-themes-fx" aria-hidden="true" data-fx="'.$attr($json).'"></canvas>'
            .'<script src="'.$attr($scriptUrl).'" data-navigate-once></script>';
    }

    /** The canvas sits behind everything; while it runs the body lets it show through (effects.js moves the body's colour). @param array<string, mixed> $t a clean theme */
    public static function css(array $t): string
    {
        return $t['effect'] === 'none' ? ''
            : '#simplify-themes-fx{position:fixed;inset:0;width:100%;height:100%;z-index:-1;pointer-events:none}html.st-fx-on body{background:transparent !important}';
    }

    /** The script's address, with its content in the name so a browser never keeps an old copy. */
    public static function scriptUrl(): string
    {
        $file = dirname(__DIR__, 2).'/'.self::SCRIPT;

        return url('/simplify-themes/effects.js').'?v='.substr((string) @md5_file($file), 0, 10);
    }
}
