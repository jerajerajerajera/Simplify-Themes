<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/** Small colour sums on #rrggbb values: mixing, the WCAG contrast between two colours, and a grey scale. */
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

    /** Lightness of each shade of a grey scale, light to dark, and how much of the picked tint each one keeps. */
    private const GREYS = [
        50 => [.985, .07], 100 => [.967, .15], 200 => [.92, .28], 300 => [.871, .48], 400 => [.705, .87], 500 => [.552, 1],
        600 => [.442, .93], 700 => [.37, .95], 800 => [.274, .9], 900 => [.21, .9], 950 => [.141, .9],
    ];

    /** The most tint a grey scale keeps: a grey stays a grey, however bright the colour it was picked from. */
    public const GREY_CHROMA = .08;

    /**
     * A grey scale (50 to 950) in the tint of one colour, as oklch() values: the same lightness steps as the panel's own
     * greys, with the colour's hue and no more of its tint than it has (TH-B11).
     *
     * @return array<int, string>
     */
    public static function greys(string $hex): array
    {
        [, $c, $h] = self::oklch($hex);
        $c = min($c, self::GREY_CHROMA);
        $out = [];
        foreach (self::GREYS as $shade => [$l, $keep]) {
            $out[$shade] = sprintf('oklch(%.3f %.3f %.3f)', $l, $c * $keep, $h);
        }

        return $out;
    }

    /** @return array{0: float, 1: float, 2: float} lightness 0..1, chroma, hue in degrees */
    public static function oklch(string $hex): array
    {
        $lin = static function (int $value): float {
            $v = $value / 255;

            return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        };
        [$r, $g, $b] = array_map($lin, self::rgb($hex));
        $l = (0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b) ** (1 / 3);
        $m = (0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b) ** (1 / 3);
        $s = (0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b) ** (1 / 3);
        $okL = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $okA = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $okB = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;
        $h = rad2deg(atan2($okB, $okA));

        return [$okL, sqrt($okA ** 2 + $okB ** 2), $h < 0 ? $h + 360 : $h];
    }

    /** @return array{0: int, 1: int, 2: int} */
    public static function rgb(string $hex): array
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
