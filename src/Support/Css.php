<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/**
 * Turns a clean theme (Theme::clean) into the one small style block a page carries. It only redefines Simplify's
 * tokens (--ps-*, tokens.css of the core) and Filament's font variables, so every screen of the core and of every
 * add-on follows by itself. The selectors are a little stronger than the core's own, so the theme wins over the core's
 * light, dark and theme-plugin values in any load order; OLED (Customize Simplify) keeps its black background.
 */
final class Css
{
    private const SPACE = [4, 8, 12, 16, 20, 24];

    private const DENSITY = ['compact' => .85, 'normal' => 1.0, 'roomy' => 1.1];

    private const LIGHT = ':root:root:root:not(.dark), :root:root :is(.ps, .ps-ui):not(.dark)';

    private const DARK = ':root:root:root.dark, :root:root :is(.ps, .ps-ui).dark';

    /**
     * @param  array<string, mixed>  $t  a clean theme
     * @param  callable(string): string  $bundledUrl  address of a bundled font file
     * @param  array<int, array{name: string, url: string, format: string}>  $uploaded  uploaded fonts by id
     * @param  array<int, string>  $images  addresses of uploaded backgrounds by id
     */
    public static function build(array $t, callable $bundledUrl, array $uploaded, array $images): string
    {
        $names = array_map(fn (array $f) => $f['name'], $uploaded);
        $css = self::faces($t, $bundledUrl, $uploaded);

        $shared = [
            '--ps-radius-sm' => round($t['radius'] * .5).'px',
            '--ps-radius' => round($t['radius'] * .72).'px',
            '--ps-radius-lg' => $t['radius'].'px',
            '--ps-radius-xl' => round($t['radius'] * 1.3).'px',
        ];
        // "inter" is the panel's own font: for text Inter, for code the panel's own monospace. Nothing to set.
        if ($t['mono'] !== 'inter') {
            $shared['--ps-mono'] = $shared['--mono-font-family'] = Fonts::stack($t['mono'], $names, 'mono');
        }
        foreach (self::SPACE as $i => $px) {
            $shared['--ps-space-'.($i + 1)] = round($px * self::DENSITY[$t['density']]).'px';
        }
        if ($t['font'] !== 'inter') {
            $shared['--ps-font'] = $shared['--font-family'] = Fonts::stack($t['font'], $names);
        }
        if ($t['shadow'] === 'none') {
            $shared += ['--ps-shadow-sm' => 'none', '--ps-shadow' => 'none', '--ps-shadow-lg' => 'none'];
        }

        $css .= self::rule(':root:root:root, :root:root :is(.ps, .ps-ui)', $shared);
        $css .= self::rule(self::LIGHT, self::palette($t, 'light'));
        $css .= self::rule(self::DARK, self::palette($t, 'dark'));
        // OLED keeps its pure black background and darker cards, as in the core.
        $css .= self::rule(':root:root:root.ps-oled.dark, :root.ps-oled:root :is(.ps, .ps-ui).dark', ['--ps-bg' => '#000', '--ps-field' => '#0a0a0c']);

        if ($t['head'] !== 'inter') {
            $css .= ':is(h1, h2, h3, .fi-header-heading, .fi-section-header-heading, .fi-modal-heading){font-family:'.Fonts::stack($t['head'], $names).'}';
        }

        return $css.self::background($t, $images).Effects::css($t);
    }

    /** @return array<string, string> */
    private static function palette(array $t, string $mode): array
    {
        $p = $t[$mode];
        $dark = $mode === 'dark';
        $line = match ($t['lines']) {
            'subtle' => Colour::mix($p['line'], $p['surface'], .45),
            'strong' => Colour::mix($p['line'], $p['ink'], .18),
            default => $p['line'],
        };
        $surface = $t['glass'] > 0 && $t['bg'] !== 'plain'
            ? 'color-mix(in srgb, '.$p['surface'].' '.(100 - $t['glass']).'%, transparent)'
            : $p['surface'];
        $out = [
            '--ps-bg' => $p['bg'],
            '--ps-surface' => $surface,
            '--ps-raised' => $p['raised'],
            '--ps-field' => Colour::mix($p['surface'], $p['bg'], $dark ? .5 : .4),
            '--ps-ink' => $p['ink'],
            '--ps-muted' => $p['muted'],
            '--ps-line' => $line,
            '--ps-line-strong' => Colour::mix($line, $p['ink'], .15),
            '--ps-contrast-bg' => $dark ? Colour::mix($p['raised'], $p['ink'], .05) : $p['ink'],
            '--ps-contrast-ink' => $dark ? $p['ink'] : Colour::mix($p['surface'], $p['bg'], .3),
            '--ps-ok' => $p['ok'],
            '--ps-warn' => $p['warn'],
            '--ps-danger' => $p['danger'],
        ];
        if ($t['shadow'] === 'strong') {
            $a = $dark ? [.45, .6, .7] : [.12, .18, .28];
            $out += ['--ps-shadow-sm' => "0 1px 3px rgb(0 0 0 / {$a[0]})", '--ps-shadow' => "0 8px 24px rgb(0 0 0 / {$a[1]})", '--ps-shadow-lg' => "0 18px 44px rgb(0 0 0 / {$a[2]})"];
        }
        if ($t['console'] === 'follow') {
            $out += ['--ps-console-bg' => $p['surface'], '--ps-console-raised' => $p['raised'], '--ps-console-line' => $p['line'], '--ps-console-ink' => $p['ink'], '--ps-console-muted' => $p['muted']];
        }

        return $out;
    }

    /** The page behind everything: a gradient or a picture. A plain background needs nothing: --ps-bg and Filament's grey already paint it. */
    private static function background(array $t, array $images): string
    {
        if ($t['bg'] === 'gradient') {
            $css = '';
            foreach (['light' => ':root:root:root:not(.dark)', 'dark' => ':root:root:root.dark'] as $mode => $selector) {
                $p = $t[$mode];
                $css .= $selector.'{background:linear-gradient(160deg, '.$p['bg'].', '.Colour::mix($p['bg'], $p['accent'], .22).') fixed !important}';
            }

            return $css.'html:root body{background:transparent !important}';
        }
        if ($t['bg'] !== 'image' || ! isset($images[$t['image']])) {
            return '';
        }
        $size = ['fill' => 'center / cover no-repeat', 'fit' => 'center / contain no-repeat', 'tile' => 'top left / auto repeat'][$t['fit']];
        // Fade lays the mode's own background colour over the picture: light in light mode, so text outside the cards
        // (filters, footer) stays readable on a bright panel; dark in dark mode.
        $layer = function (string $url, string $veil) use ($t, $size): string {
            $rgb = implode(' ', Colour::rgb($veil));
            $fade = sprintf('linear-gradient(rgb(%s / %.2f), rgb(%s / %.2f))', $rgb, $t['dim'] / 100, $rgb, $t['dim'] / 100);

            return $fade.', url("'.addcslashes($url, '"\\').'") '.$size;
        };
        $dark = $t['image_dark'] !== null && isset($images[$t['image_dark']]) ? $images[$t['image_dark']] : $images[$t['image']];
        $css = 'html:root::before{content:"";position:fixed;inset:'.(-2 * $t['blur']).'px;z-index:-1;pointer-events:none;background:'.$layer($images[$t['image']], $t['light']['bg'])
            .($t['blur'] > 0 ? ';filter:blur('.$t['blur'].'px)' : '').'}';
        $css .= 'html:root.dark::before{background:'.$layer($dark, '#000000').'}';

        return $css.'html:root body{background:transparent !important}';
    }

    /** @param  array<int, array{name: string, url: string, format: string}>  $uploaded */
    private static function faces(array $t, callable $bundledUrl, array $uploaded): string
    {
        $css = '';
        foreach (array_unique([$t['font'], $t['head'], $t['mono']]) as $key) {
            if (($id = Fonts::uploadedId($key)) !== null) {
                if (isset($uploaded[$id])) {
                    $f = $uploaded[$id];
                    $css .= '@font-face{font-family:"'.Fonts::uploadedFamily($f['name']).'";src:url("'.addcslashes($f['url'], '"\\').'") format("'.$f['format'].'");font-weight:100 900;font-display:swap}';
                }

                continue;
            }
            [, $family, , $files] = Fonts::BUNDLED[$key] ?? Fonts::BUNDLED['inter'];
            foreach ($files as [$file, $weight]) {
                $css .= '@font-face{font-family:"'.$family.'";src:url("'.addcslashes($bundledUrl($file), '"\\').'") format("woff2");font-weight:'.$weight.';font-display:swap}';
            }
        }

        return $css;
    }

    /** @param  array<string, string>  $vars */
    private static function rule(string $selector, array $vars): string
    {
        $body = '';
        foreach ($vars as $name => $value) {
            $body .= $name.':'.$value.';';
        }

        return $selector.'{'.$body.'}';
    }
}
