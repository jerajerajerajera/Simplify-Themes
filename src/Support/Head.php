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
            FilamentColor::register([
                'primary' => Color::hex($light['accent']),
                'gray' => Color::hex($theme['data']['gray']),
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

    /** @param array<string, mixed> $data a clean theme */
    public static function css(array $data): string
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

        return Css::build($data, fn (string $file) => url('/simplify-themes/font/'.$file), $uploaded, $images);
    }
}
