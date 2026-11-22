<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/**
 * What a theme holds, and the one place that checks it. Every value that reaches a style passes clean(): a colour is
 * #rrggbb, a number lies in its range, a choice is one of a fixed list. Anything else falls back to Simplify's own
 * look, so a stored theme can never carry CSS of its own.
 */
final class Theme
{
    /** The colour roles, each with a light and a dark value; they map onto Simplify's --ps-* tokens. */
    public const ROLES = ['accent', 'bg', 'surface', 'raised', 'ink', 'muted', 'line', 'ok', 'warn', 'danger'];

    public const CHOICES = [
        'bg' => ['plain', 'gradient', 'image'],
        'fit' => ['fill', 'fit', 'tile'],
        'density' => ['compact', 'normal', 'roomy'],
        'shadow' => ['none', 'soft', 'strong'],
        'lines' => ['subtle', 'normal', 'strong'],
        'console' => ['black', 'follow'],
    ];

    /** min, max */
    public const RANGES = ['dim' => [0, 80], 'blur' => [0, 20], 'glass' => [0, 50], 'radius' => [0, 22]];

    public const FONT_SLOTS = ['font', 'head', 'mono'];

    public const NAME_MAX = 40;

    /**
     * A clean theme from anything: a stored row, a form, an imported file.
     *
     * @param  array<string, mixed>  $in
     * @param  list<string>  $fonts  font keys that may be used (bundled and uploaded)
     * @param  list<int>  $images  uploaded background ids that may be used
     * @return array<string, mixed>
     */
    public static function clean(array $in, array $fonts, array $images): array
    {
        $base = Presets::SIMPLIFY;
        $out = [];
        foreach (['light', 'dark'] as $mode) {
            foreach (self::ROLES as $role) {
                $value = is_string($in[$mode][$role] ?? null) ? strtolower(trim($in[$mode][$role])) : null;
                $out[$mode][$role] = Colour::valid($value) ? $value : $base[$mode][$role];
            }
        }
        $gray = is_string($in['gray'] ?? null) ? strtolower(trim($in['gray'])) : null;
        $out['gray'] = Colour::valid($gray) ? $gray : $base['gray'];
        foreach (self::CHOICES as $key => $allowed) {
            $out[$key] = in_array($in[$key] ?? null, $allowed, true) ? $in[$key] : $base[$key];
        }
        foreach (self::RANGES as $key => [$min, $max]) {
            $out[$key] = is_numeric($in[$key] ?? null) ? max($min, min($max, (int) $in[$key])) : $base[$key];
        }
        foreach (self::FONT_SLOTS as $slot) {
            $out[$slot] = in_array($in[$slot] ?? null, $fonts, true) ? $in[$slot] : $base[$slot];
        }
        foreach (['image', 'image_dark'] as $key) {
            $id = is_numeric($in[$key] ?? null) ? (int) $in[$key] : null;
            $out[$key] = $id !== null && in_array($id, $images, true) ? $id : null;
        }
        if ($out['bg'] === 'image' && $out['image'] === null) {
            $out['bg'] = 'plain';
        }

        return $out;
    }

    /** A theme name: trimmed, at most NAME_MAX characters, never empty. */
    public static function name(mixed $name): ?string
    {
        $name = is_string($name) ? trim(preg_replace('/\s+/u', ' ', $name) ?? '') : '';

        return $name === '' || mb_strlen($name) > self::NAME_MAX ? null : $name;
    }

    /** Every font key a theme may use, given the uploaded fonts (id => name). @param array<int, string> $uploaded @return list<string> */
    public static function fontKeys(array $uploaded): array
    {
        return [...array_keys(Fonts::BUNDLED), ...array_map(Fonts::uploadedKey(...), array_keys($uploaded))];
    }

    /**
     * Readability of the main pairs in one mode, against WCAG AA (4.5:1). A warning never blocks Save.
     *
     * @param  array<string, mixed>  $theme
     * @return array<string, float>
     */
    public static function contrast(array $theme, string $mode): array
    {
        $p = $theme[$mode];

        return [
            'text' => Colour::contrast($p['ink'], $p['surface']),
            'muted' => Colour::contrast($p['muted'], $p['surface']),
            'button' => Colour::contrast(Colour::on($p['accent']), $p['accent']),
        ];
    }
}
