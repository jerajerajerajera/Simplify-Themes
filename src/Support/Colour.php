<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/** Small colour sums on #rrggbb values: mixing, and the WCAG contrast between two colours. */
final class Colour
{
    public const PATTERN = '/^#[0-9a-f]{6}$/';

    public static function valid(mixed $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN, $value) === 1;
    }

    /** $a with $weight (0..1) of $b mixed in. */
    public static function mix(string $a, string $b, float $weight): string
    {
        $x = self::rgb($a);
        $y = self::rgb($b);
        $out = '#';
        foreach ([0, 1, 2] as $i) {
            $out .= sprintf('%02x', (int) round($x[$i] * (1 - $weight) + $y[$i] * $weight));
        }

        return $out;
    }

    /** The WCAG 2 contrast ratio, from 1 (none) to 21 (black on white). */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** White or near-black text, whichever reads better on this colour. */
    public static function on(string $colour): string
    {
        return self::contrast($colour, '#ffffff') >= self::contrast($colour, '#111118') ? '#ffffff' : '#111118';
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function rgb(string $hex): array
    {
        return [(int) hexdec(substr($hex, 1, 2)), (int) hexdec(substr($hex, 3, 2)), (int) hexdec(substr($hex, 5, 2))];
    }

    private static function luminance(string $hex): float
    {
        $channel = static function (int $value): float {
            $v = $value / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        };
        [$r, $g, $b] = self::rgb($hex);

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }
}
