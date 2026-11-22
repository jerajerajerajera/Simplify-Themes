<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/**
 * The ready-made themes a panel starts with. Simplify's own look is SIMPLIFY (the core's tokens.css); the others are
 * added once, as normal themes that can be changed or deleted.
 */
final class Presets
{
    public const SIMPLIFY = [
        'light' => ['accent' => '#2563eb', 'bg' => '#f4f4f5', 'surface' => '#ffffff', 'raised' => '#eeeef1', 'ink' => '#26262e', 'muted' => '#666672', 'line' => '#dedee5', 'ok' => '#15803d', 'warn' => '#a16207', 'danger' => '#be3348'],
        'dark' => ['accent' => '#3b82f6', 'bg' => '#101012', 'surface' => '#1a1a1f', 'raised' => '#25252c', 'ink' => '#ededf2', 'muted' => '#b0b0bb', 'line' => '#33333c', 'ok' => '#86cf9d', 'warn' => '#f3be78', 'danger' => '#f3a6ae'],
        'gray' => '#71717a', 'bg' => 'plain', 'image' => null, 'image_dark' => null, 'dim' => 10, 'blur' => 0, 'fit' => 'fill', 'glass' => 0,
        'font' => 'inter', 'head' => 'inter', 'mono' => 'inter',
        'radius' => 14, 'density' => 'normal', 'shadow' => 'soft', 'lines' => 'normal', 'console' => 'black',
        'effect' => 'none', 'fx_amount' => 100, 'fx_speed' => 100, 'fx_size' => 100, 'fx_strength' => 75, 'fx_glow' => 0, 'fx_dir' => 180, 'fx_colour' => null,
    ];

    /** name => changes on top of SIMPLIFY. */
    public const READY = [
        'Ocean night' => [
            'light' => ['accent' => '#0e7490', 'bg' => '#eef5f7', 'surface' => '#ffffff', 'raised' => '#e3eef2', 'ink' => '#13303a', 'muted' => '#557079', 'line' => '#d3e2e7', 'ok' => '#15803d', 'warn' => '#a16207', 'danger' => '#be3348'],
            'dark' => ['accent' => '#38bdf8', 'bg' => '#071820', 'surface' => '#0d2530', 'raised' => '#15323e', 'ink' => '#e4f2f6', 'muted' => '#93b3bd', 'line' => '#1f4250', 'ok' => '#86cf9d', 'warn' => '#f3be78', 'danger' => '#f3a6ae'],
            'gray' => '#64748b', 'bg' => 'gradient', 'font' => 'outfit', 'head' => 'grotesk',
        ],
        'Forest' => [
            'light' => ['accent' => '#3f7d3a', 'bg' => '#f1f4ee', 'surface' => '#ffffff', 'raised' => '#e6ece1', 'ink' => '#1f2a1d', 'muted' => '#5d6b58', 'line' => '#d6dfd0', 'ok' => '#2f7d32', 'warn' => '#9a6b00', 'danger' => '#b83a3a'],
            'dark' => ['accent' => '#8fcf7a', 'bg' => '#0f150e', 'surface' => '#182117', 'raised' => '#222d20', 'ink' => '#e7efe3', 'muted' => '#a6b5a0', 'line' => '#2e3b2b', 'ok' => '#9fd88c', 'warn' => '#e8c26a', 'danger' => '#f0a3a3'],
            'gray' => '#71806b', 'bg' => 'gradient', 'font' => 'nunito', 'head' => 'nunito', 'radius' => 18, 'density' => 'roomy', 'console' => 'follow',
        ],
        'Pixel' => [
            'light' => ['accent' => '#7c3aed', 'bg' => '#f3f1f8', 'surface' => '#ffffff', 'raised' => '#ebe7f4', 'ink' => '#231c33', 'muted' => '#655c78', 'line' => '#dad3e8', 'ok' => '#15803d', 'warn' => '#a16207', 'danger' => '#be3348'],
            'dark' => ['accent' => '#a78bfa', 'bg' => '#110d1a', 'surface' => '#1b1528', 'raised' => '#261e37', 'ink' => '#eee9f8', 'muted' => '#b3a9c8', 'line' => '#352b4a', 'ok' => '#86cf9d', 'warn' => '#f3be78', 'danger' => '#f3a6ae'],
            'gray' => '#71687f', 'font' => 'jetbrains', 'head' => 'pixel', 'mono' => 'jetbrains', 'radius' => 4, 'density' => 'compact', 'shadow' => 'none', 'lines' => 'strong',
        ],
        'Paper' => [
            'light' => ['accent' => '#9a3412', 'bg' => '#f6f3ee', 'surface' => '#fffdf9', 'raised' => '#efe9df', 'ink' => '#2b2520', 'muted' => '#6e645a', 'line' => '#e2d9cc', 'ok' => '#3f7d3a', 'warn' => '#9a6b00', 'danger' => '#b83a3a'],
            'dark' => ['accent' => '#fb923c', 'bg' => '#15120f', 'surface' => '#1f1b17', 'raised' => '#2a2520', 'ink' => '#f1ebe3', 'muted' => '#b8ad9f', 'line' => '#3a332c', 'ok' => '#9fd88c', 'warn' => '#e8c26a', 'danger' => '#f0a3a3'],
            'gray' => '#78716c', 'font' => 'serif', 'head' => 'serif', 'radius' => 8, 'shadow' => 'none', 'console' => 'follow',
        ],
    ];

    /** Simplify's own look as a full theme, with the gradient its colours give. @return array<string, mixed> */
    public static function simplify(): array
    {
        return self::withGradient(self::SIMPLIFY);
    }

    /** @return array<string, array<string, mixed>> name => full theme */
    public static function ready(): array
    {
        return array_map(fn (array $changes) => self::withGradient(array_replace(self::SIMPLIFY, $changes)), self::READY);
    }

    /** @param array<string, mixed> $theme @return array<string, mixed> */
    private static function withGradient(array $theme): array
    {
        return $theme + ['grad' => ['light' => Gradient::fromPalette($theme['light']), 'dark' => Gradient::fromPalette($theme['dark'])]];
    }
}
