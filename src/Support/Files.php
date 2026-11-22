<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Uploaded fonts and backgrounds. A file is checked by what it holds, never by its name: a font must be WOFF2 or
 * TrueType with a sane table directory, a picture must be PNG, JPEG or WebP and is drawn again as WebP (which drops
 * anything hidden in it). Files live on the private local disk under a random name and are served by one route.
 */
final class Files
{
    public const TABLE = 'simplify_theme_files';

    public const DIRECTORY = 'simplify-themes';

    /** A stored file: 32 letters and digits, then its type. */
    public const PATTERN = '[A-Za-z0-9]{32}\.(woff2|ttf|webp)';

    public const FONT_MAX = 2 * 1024 * 1024;

    public const IMAGE_MAX = 5 * 1024 * 1024;

    /** The longest side of a background after upload. */
    public const IMAGE_SIDE = 2560;

    public const TYPES = ['woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'webp' => 'image/webp'];

    /**
     * What a font file is, from its bytes: "woff2", "ttf" or null for anything else (OpenType CFF, WOFF 1, SVG, junk).
     * TrueType needs a table directory that fits the file and holds the tables every font has.
     */
    public static function fontType(string $bytes): ?string
    {
        $size = strlen($bytes);
        if ($size < 48) {
            return null;
        }
        $tag = substr($bytes, 0, 4);
        if ($tag === 'wOF2') {
            $length = unpack('N', $bytes, 8)[1];
            $tables = unpack('n', $bytes, 12)[1];

            return $length === $size && $tables >= 1 && $tables <= 64 ? 'woff2' : null;
        }
        if ($tag !== "\x00\x01\x00\x00" && $tag !== 'true') {
            return null;
        }
        $tables = unpack('n', $bytes, 4)[1];
        if ($tables < 1 || $tables > 64 || 12 + 16 * $tables > $size) {
            return null;
        }
        $found = [];
        for ($i = 0; $i < $tables; $i++) {
            $record = 12 + 16 * $i;
            ['offset' => $offset, 'length' => $length] = unpack('Noffset/Nlength', $bytes, $record + 8);
            if ($offset + $length > $size) {
                return null;
            }
            $found[] = substr($bytes, $record, 4);
        }

        return array_diff(['cmap', 'head', 'name'], $found) === [] ? 'ttf' : null;
    }

    /** Stores a font under the name people gave it. @throws RuntimeException with a translation key */
    public static function storeFont(UploadedFile $file, string $name): int
    {
        if (preg_match(Fonts::NAME_PATTERN, $name) !== 1) {
            throw new RuntimeException('font_name_invalid');
        }
        if ($file->getSize() > self::FONT_MAX) {
            throw new RuntimeException('font_too_big');
        }
        $bytes = (string) $file->get();
        $type = self::fontType($bytes);
        if ($type === null) {
            throw new RuntimeException('font_not_valid');
        }

        return self::put('font', $name, $type, $bytes);
    }

    /** Stores a background, drawn again as WebP of at most IMAGE_SIDE pixels. @throws RuntimeException with a translation key */
    public static function storeImage(UploadedFile $file): int
    {
        if ($file->getSize() > self::IMAGE_MAX) {
            throw new RuntimeException('image_too_big');
        }
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('image_no_gd');
        }
        $bytes = (string) $file->get();
        $info = @getimagesizefromstring($bytes);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)
            || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) {
            throw new RuntimeException('image_not_valid');
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException('image_not_valid');
        }
        $scale = min(1, self::IMAGE_SIDE / max($info[0], $info[1]));
        if ($scale < 1) {
            $image = imagescale($image, (int) round($info[0] * $scale), (int) round($info[1] * $scale)) ?: $image;
        }
        ob_start();
        imagewebp($image, null, 82);
        $webp = (string) ob_get_clean();
        if ($webp === '') {
            throw new RuntimeException('image_not_valid');
        }
        $label = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 60, '');

        return self::put('image', preg_replace('/[^A-Za-z0-9 ._-]/', '', $label) ?: 'background', 'webp', $webp);
    }

    /** @return list<array{id: int, kind: string, name: string, file: string, size: int}> */
    public static function all(?string $kind = null): array
    {
        return DB::table(self::TABLE)->when($kind, fn ($q) => $q->where('kind', $kind))->orderBy('id')->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'kind' => (string) $row->kind, 'name' => (string) $row->name, 'file' => (string) $row->file, 'size' => (int) $row->size])
            ->all();
    }

    public static function url(string $file): string
    {
        return url('/simplify-themes/file/'.$file);
    }

    /** Removes a file no theme uses any more. Returns false when a theme still uses it. */
    public static function remove(int $id): bool
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();
        if ($row === null) {
            return true;
        }
        if (self::inUse($id, $row->kind)) {
            return false;
        }
        Storage::disk('local')->delete(self::DIRECTORY.'/'.$row->file);
        DB::table(self::TABLE)->where('id', $id)->delete();
        Store::flush();

        return true;
    }

    /** Whether a theme uses this file: a font in one of its slots, a picture as a background. */
    public static function inUse(int $id, string $kind): bool
    {
        foreach (Store::themes() as $theme) {
            if (self::usedBy($theme['data'], $id, $kind)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether one theme shows this file: a font in one of its slots, a picture as the background of light or dark. A
     * picture that stays in a theme whose background is now plain or a gradient is not in use (TH-B12).
     *
     * @param  array<string, mixed>  $d  a clean theme
     */
    public static function usedBy(array $d, int $id, string $kind): bool
    {
        if ($kind === 'font') {
            return in_array(Fonts::uploadedKey($id), [$d['font'], $d['head'], $d['mono']], true);
        }

        return ($d['bg'] === 'image' && $d['image'] === $id) || ($d['bg_dark'] === 'image' && ($d['image_dark'] ?? $d['image']) === $id);
    }

    private static function put(string $kind, string $name, string $type, string $bytes): int
    {
        $file = Str::random(32).'.'.$type;
        Storage::disk('local')->put(self::DIRECTORY.'/'.$file, $bytes);

        $id = (int) DB::table(self::TABLE)->insertGetId(['kind' => $kind, 'name' => $name, 'file' => $file, 'size' => strlen($bytes), 'created_at' => now()]);
        Store::flush();

        return $id;
    }
}
