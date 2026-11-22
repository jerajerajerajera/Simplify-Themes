<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/**
 * A gradient background of 2 to 6 colours, one for light and one for dark mode. Each colour has a place (0-100), and
 * a blend point between two colours (5-95: where they meet halfway). Linear, radial or conic, and when it repeats the
 * last colour blends back into the first, so the seam between two repeats is as soft as the rest.
 */
final class Gradient
{
    public const TYPES = ['linear', 'radial', 'conic'];

    public const MAX = 6;

    /** Ready-made gradients for a quick start: colour and place. */
    public const PRESETS = [
        [['#1e1b4b', 0], ['#9d174d', 45], ['#f97316', 80], ['#fde68a', 100]],
        [['#04263a', 0], ['#0e7490', 55], ['#67e8f9', 100]],
        [['#022c22', 0], ['#059669', 35], ['#6366f1', 70], ['#1e1b4b', 100]],
        [['#0c0a09', 0], ['#7f1d1d', 60], ['#ea580c', 100]],
        [['#f8fafc', 0], ['#e0e7ff', 50], ['#fce7f3', 100]],
    ];

    /** The gradient a theme had before it had its own: its background into a little of its accent. @param array<string, string> $p a palette @return array<string, mixed> */
    public static function fromPalette(array $p): array
    {
        return [
            'type' => 'linear', 'angle' => 160, 'cx' => 50, 'cy' => 30, 'repeat' => false, 'size' => 40, 'wrap' => 50,
            'stops' => [['c' => $p['bg'], 'p' => 0], ['c' => Colour::mix($p['bg'], $p['accent'], .22), 'p' => 100]],
            'mids' => [50, 50],
        ];
    }

    /**
     * A clean gradient from anything; what is missing or wrong comes from $fallback.
     *
     * @param  array<string, mixed>  $fallback  a clean gradient
     * @return array<string, mixed>
     */
    public static function clean(mixed $in, array $fallback): array
    {
        if (! is_array($in)) {
            return $fallback;
        }
        $num = fn (string $key, int $min, int $max) => is_numeric($in[$key] ?? null) ? max($min, min($max, (int) round((float) $in[$key]))) : $fallback[$key];
        $stops = [];
        $mids = [];
        foreach (array_slice(is_array($in['stops'] ?? null) ? array_values($in['stops']) : [], 0, self::MAX) as $i => $stop) {
            $c = is_array($stop) && is_string($stop['c'] ?? null) ? strtolower(trim($stop['c'])) : null;
            if (! Colour::valid($c) || ! is_numeric($stop['p'] ?? null)) {
                continue;
            }
            $stops[] = ['c' => $c, 'p' => max(0, min(100, (int) round((float) $stop['p'])))];
            $mid = is_array($in['mids'] ?? null) ? ($in['mids'][$i] ?? null) : null;
            $mids[] = is_numeric($mid) ? max(5, min(95, (int) round((float) $mid))) : 50;
        }

        return [
            'type' => in_array($in['type'] ?? null, self::TYPES, true) ? $in['type'] : $fallback['type'],
            'angle' => $num('angle', 0, 359), 'cx' => $num('cx', 0, 100), 'cy' => $num('cy', 0, 100),
            'repeat' => is_bool($in['repeat'] ?? null) ? $in['repeat'] : $fallback['repeat'],
            'size' => $num('size', 5, 100), 'wrap' => $num('wrap', 5, 95),
            'stops' => count($stops) >= 2 ? $stops : $fallback['stops'],
            'mids' => count($stops) >= 2 ? $mids : $fallback['mids'],
        ];
    }

    /** The CSS image. Only checked numbers and #rrggbb colours reach it. @param array<string, mixed> $g a clean gradient */
    public static function css(array $g): string
    {
        $k = $g['repeat'] ? $g['size'] / 100 : 1;
        $stops = [];
        foreach ($g['stops'] as $i => $stop) {
            $stops[] = $stop + ['m' => $g['mids'][$i] ?? 50];
        }
        usort($stops, fn (array $a, array $b) => $a['p'] <=> $b['p']);
        if ($g['repeat']) {
            $stops[count($stops) - 1]['m'] = $g['wrap'];
            $stops[] = ['c' => $stops[0]['c'], 'p' => 100 + $stops[0]['p'], 'm' => 50];
        }
        $parts = [];
        foreach ($stops as $i => $stop) {
            $parts[] = $stop['c'].' '.self::pct($stop['p'] * $k);
            if (isset($stops[$i + 1])) {
                $parts[] = self::pct(($stop['p'] + ($stops[$i + 1]['p'] - $stop['p']) * $stop['m'] / 100) * $k);
            }
        }
        $list = implode(', ', $parts);
        $r = $g['repeat'] ? 'repeating-' : '';

        return match ($g['type']) {
            'radial' => "{$r}radial-gradient(circle at {$g['cx']}% {$g['cy']}%, $list)",
            'conic' => "{$r}conic-gradient(from {$g['angle']}deg at {$g['cx']}% {$g['cy']}%, $list)",
            default => "{$r}linear-gradient({$g['angle']}deg, $list)",
        };
    }

    private static function pct(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.').'%';
    }
}
