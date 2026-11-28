<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Puts the active theme on a page: Filament's colours (Pelican's own pages, with and without the core) and one style
 * block with the tokens, fonts and background. Nothing at all with Simplify's own look.
 */
final class Head
{
    /** Filament's palettes from the theme, so Pelican's pages follow too. Runs while Filament serves a page. */
    public static function colours(): void
    {
        $theme = Store::active(user());
        if ($theme === null) {
            return;
        }
        $light = $theme['data']['light'];
        try {
            // Filament's own scale from one colour is as bright as an accent: helper text and table rows turned blue
            // (TH-B11). A Filament with oklch() palettes gets the theme's grey scale instead.
            $gray = Color::hex($theme['data']['gray']);
            if (is_string($gray[500] ?? null) && str_starts_with($gray[500], 'oklch(')) {
                $gray = Colour::greys($theme['data']['gray']);
            }
            FilamentColor::register([
                'primary' => Color::hex($light['accent']),
                'gray' => $gray,
                'success' => Color::hex($light['ok']),
                'warning' => Color::hex($light['warn']),
                'danger' => Color::hex($light['danger']),
            ]);
        } catch (Throwable) {
            // A Filament without these helpers keeps its own colours; the style block below still applies.
        }
    }

    public static function style(): HtmlString
    {
        $theme = Store::active(user());
        if ($theme === null) {
            return new HtmlString('');
        }

        return new HtmlString('<style id="simplify-themes">'.self::css($theme['data']).'</style>');
    }

    /** The moving background at the end of the page, when the theme has one. */
    public static function effect(): HtmlString
    {
        $theme = Store::active(user());

        return new HtmlString($theme === null ? '' : Effects::tag($theme['data'], Effects::scriptUrl()));
    }

    /** @param array<string, mixed> $data a clean theme; $status: the core's public status page instead of the panel */
    public static function css(array $data, bool $status = false): string
    {
        $uploaded = [];
        $images = [];
        foreach (Files::all() as $file) {
            if ($file['kind'] === 'font') {
                $uploaded[$file['id']] = ['name' => $file['name'], 'url' => Files::url($file['file']), 'format' => str_ends_with($file['file'], '.ttf') ? 'truetype' : 'woff2'];
            } else {
                $images[$file['id']] = Files::url($file['file']);
            }
        }

        $font = fn (string $file) => url('/simplify-themes/font/'.$file);

        return $status ? Css::status($data, $font, $uploaded, $images) : Css::build($data, $font, $uploaded, $images);
    }
}
