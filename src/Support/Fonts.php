<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

/**
 * The free fonts that ship with the add-on (resources/fonts, SIL Open Font License, see LICENSES.txt), and uploaded
 * fonts as "u<id>". Only these keys ever reach a style: a family name is never taken from a request or a font file.
 */
final class Fonts
{
    /** key => [label, CSS family, generic fallback, [[file, weight range]]]. "inter" is the panel's own font: nothing to load. */
    public const BUNDLED = [
        'inter' => ['Inter', 'Inter', 'sans', []],
        'atkinson' => ['Atkinson Hyperlegible', 'Simplify Atkinson', 'sans', [['atkinson-hyperlegible-400.woff2', '400'], ['atkinson-hyperlegible-700.woff2', '700']]],
        'plex' => ['IBM Plex Sans', 'Simplify Plex Sans', 'sans', [['ibm-plex-sans.woff2', '100 700']]],
        'lexend' => ['Lexend', 'Simplify Lexend', 'sans', [['lexend.woff2', '100 900']]],
        'outfit' => ['Outfit', 'Simplify Outfit', 'sans', [['outfit.woff2', '100 900']]],
        'grotesk' => ['Space Grotesk', 'Simplify Space Grotesk', 'sans', [['space-grotesk.woff2', '300 700']]],
        'nunito' => ['Nunito', 'Simplify Nunito', 'sans', [['nunito.woff2', '200 1000']]],
        'fredoka' => ['Fredoka', 'Simplify Fredoka', 'sans', [['fredoka.woff2', '300 700']]],
        'serif' => ['Source Serif 4', 'Simplify Source Serif', 'serif', [['source-serif-4.woff2', '200 900']]],
        'jetbrains' => ['JetBrains Mono', 'Simplify JetBrains Mono', 'mono', [['jetbrains-mono.woff2', '100 800']]],
        'pixel' => ['Silkscreen', 'Simplify Silkscreen', 'mono', [['silkscreen.woff2', '400']]],
    ];

    public const FALLBACK = [
        'sans' => 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif',
        'serif' => 'ui-serif, Georgia, Cambria, "Times New Roman", serif',
        'mono' => 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
    ];

    /** A bundled font file: lower case, digits and dashes, .woff2. */
    public const FILE_PATTERN = '[a-z0-9-]{1,40}\.woff2';

    /** An uploaded font's name as people typed it, and as its CSS family (with a prefix so it never shadows a system font). */
    public const NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9 ]{0,39}$/';

    public static function uploadedKey(int $id): string
    {
        return 'u'.$id;
    }

    /** The id of an uploaded font key, or null for a bundled one. */
    public static function uploadedId(string $key): ?int
    {
        return preg_match('/^u([1-9][0-9]{0,9})$/', $key, $m) === 1 ? (int) $m[1] : null;
    }

    public static function uploadedFamily(string $name): string
    {
        return 'Themes '.$name;
    }

    /**
     * A font-family value for a key.
     *
     * @param  array<int, string>  $uploaded  id => name of the uploaded fonts
     */
    public static function stack(string $key, array $uploaded, string $generic = 'sans'): string
    {
        if (($id = self::uploadedId($key)) !== null && isset($uploaded[$id])) {
            return '"'.self::uploadedFamily($uploaded[$id]).'", '.self::FALLBACK[$generic];
        }
        [, $family, $fallback] = self::BUNDLED[$key] ?? self::BUNDLED['inter'];

        return '"'.$family.'", '.self::FALLBACK[$fallback];
    }
}
