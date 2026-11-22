<?php

declare(strict_types=1);

namespace Simplify\Themes\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Simplify\Themes\Support\Files;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the theme's fonts and background. Open without login, like the panel logo: the login page wears the theme
 * too. Only names that match a fixed pattern reach the disk, and every file goes out with its own type and nosniff.
 */
final class FileController
{
    /** An uploaded font or background. Every upload gets a new random name, so a name never changes content. */
    public function uploaded(string $file): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        $path = Files::DIRECTORY.'/'.$file;
        abort_unless($disk->exists($path), 404);

        return self::send($disk->path($path), Files::TYPES[pathinfo($file, PATHINFO_EXTENSION)]);
    }

    /** A font that ships with the add-on. */
    public function bundled(string $file): BinaryFileResponse
    {
        $path = dirname(__DIR__, 3).'/resources/fonts/'.$file;
        abort_unless(is_file($path), 404);

        return self::send($path, 'font/woff2');
    }

    private static function send(string $path, string $type): BinaryFileResponse
    {
        return response()->file($path, [
            'Content-Type' => $type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
