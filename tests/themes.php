<?php

// The checks of a theme that need no panel: every value that reaches a style is checked, the style block only sets
// tokens, and a font upload is recognised by its bytes. Run: php tests/themes.php
declare(strict_types=1);

foreach (['Colour', 'Fonts', 'Presets', 'Theme', 'Css', 'Files'] as $class) {
    require __DIR__."/../src/Support/$class.php";
}

use Simplify\Themes\Support\Colour;
use Simplify\Themes\Support\Css;
use Simplify\Themes\Support\Files;
use Simplify\Themes\Support\Fonts;
use Simplify\Themes\Support\Presets;
use Simplify\Themes\Support\Theme;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    echo ($ok ? 'PASS ' : 'FAIL ').$what."\n";
    if (! $ok) {
        $failures[] = $what;
    }
}

$fonts = Theme::fontKeys([7 => 'Harbour Sans']);
$clean = fn (array $in) => Theme::clean($in, $fonts, [3]);

// Colours
check(abs(Colour::contrast('#000000', '#ffffff') - 21) < .01, 'black on white is 21:1');
check(Colour::mix('#000000', '#ffffff', .5) === '#808080', 'mixing black and white gives grey');
check(Colour::on('#2563eb') === '#ffffff' && Colour::on('#fde047') === '#111118', 'button text picks the readable colour');

// Clean: nothing but checked values survives
$evil = Presets::SIMPLIFY;
$evil['light']['accent'] = 'red;}body{display:none';
$evil['dark']['bg'] = '#ZZZZZZ';
$evil['radius'] = 999;
$evil['dim'] = -5;
$evil['bg'] = 'url(javascript:x)';
$evil['font'] = '"><script>';
$evil['head'] = 'u7';
$evil['mono'] = 'u8';
$evil['image'] = 9;
$c = $clean($evil);
check($c['light']['accent'] === Presets::SIMPLIFY['light']['accent'], 'a colour that is not #rrggbb falls back');
check($c['dark']['bg'] === Presets::SIMPLIFY['dark']['bg'], 'a broken hex falls back');
check($c['radius'] === 22 && $c['dim'] === 0, 'numbers are kept inside their range');
check($c['bg'] === 'plain', 'a background that is not a known choice falls back');
check($c['font'] === 'inter' && $c['head'] === 'u7' && $c['mono'] === 'inter', 'only known fonts, uploaded ones by id');
check($c['image'] === null, 'a picture that does not exist is dropped');
check($clean(['bg' => 'image', 'image' => 3])['bg'] === 'image' && $clean(['bg' => 'image'])['bg'] === 'plain', 'a picture background needs a picture');
check($clean([]) === $clean(Presets::SIMPLIFY), "an empty theme is Simplify's own look");
check(array_keys($clean([])) === array_keys($clean($c)), 'a clean theme always has the same keys');
foreach (Presets::ready() as $name => $theme) {
    check($clean($theme) == $theme, "ready-made theme $name is clean as it is");
}
check(Theme::name('  Ocean   night ') === 'Ocean night' && Theme::name('') === null && Theme::name(str_repeat('a', 41)) === null, 'names are trimmed and limited');

// Css
$url = fn (string $file) => '/simplify-themes/font/'.$file;
$css = Css::build($clean(Presets::ready()['Ocean night']), $url, [], []);
check(! str_contains($css, '<') && ! str_contains($css, 'javascript'), 'the style block holds no markup');
check(str_contains($css, '--ps-surface:#ffffff') && str_contains($css, '--ps-bg:#071820'), 'the style block sets the core tokens for light and dark');
check(str_contains($css, '/simplify-themes/font/outfit.woff2') && str_contains($css, 'space-grotesk.woff2'), 'only the fonts in use are loaded, from the panel itself');
check(! str_contains($css, 'fonts.googleapis') && ! preg_match('#url\("https?://#', $css), 'no font or picture comes from another site');
check(substr_count($css, '{') === substr_count($css, '}'), 'braces are balanced');
$plain = Css::build($clean([]), $url, [], []);
check(! str_contains($plain, '@font-face') && ! str_contains($plain, 'background:'), "Simplify's own look adds no fonts and no background");
$pic = Css::build($clean(['bg' => 'image', 'image' => 3, 'blur' => 8, 'dim' => 30]), $url, [], [3 => '/simplify-themes/file/'.str_repeat('a', 32).'.webp']);
check(str_contains($pic, 'html:root::before') && str_contains($pic, 'blur(8px)'), 'a picture background is drawn behind everything, blurred');
check(str_contains($pic, 'rgb(244 244 245 / 0.30)') && str_contains($pic, 'html:root.dark::before{background:linear-gradient(rgb(0 0 0 / 0.30)'), 'Fade is a light veil in light mode and a dark one in dark mode');
$up = Css::build($clean(['font' => 'u7']), $url, [7 => ['name' => 'Harbour Sans', 'url' => '/simplify-themes/file/x.woff2', 'format' => 'woff2']], []);
check(str_contains($up, 'font-family:"Themes Harbour Sans"') && str_contains($up, '"Themes Harbour Sans", ui-sans-serif'), 'an uploaded font gets its own family name');

// Fonts by their bytes
$woff2 = file_get_contents(__DIR__.'/../resources/fonts/outfit.woff2');
check(Files::fontType($woff2) === 'woff2', 'a real WOFF2 font is recognised');
check(Files::fontType(substr($woff2, 0, -10)) === null, 'a cut-off WOFF2 is refused');
check(Files::fontType('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>                ') === null, 'an SVG named .ttf is refused');
check(Files::fontType('OTTO'.str_repeat("\0", 60)) === null, 'OpenType CFF (OTF) is refused');
// A minimal TrueType table directory: cmap, head, name, each inside the file.
$ttf = pack('NnnnnN', 0x00010000, 3, 0, 0, 0, 0);
$ttf = substr($ttf, 0, 12);
$offset = 12 + 16 * 3;
foreach (['cmap', 'head', 'name'] as $i => $tag) {
    $ttf .= $tag.pack('NNN', 0, $offset + 4 * $i, 4);
}
$ttf .= str_repeat("\0", 12);
check(Files::fontType($ttf) === 'ttf', 'a TrueType font with cmap, head and name is recognised');
check(Files::fontType(substr($ttf, 0, 12).'cmap'.pack('NNN', 0, 9999, 4).substr($ttf, 28)) === null, 'a table outside the file is refused');
check(preg_match('/^'.Files::PATTERN.'$/', str_repeat('a', 32).'.webp') === 1 && preg_match('/^'.Files::PATTERN.'$/', '../.env') === 0, 'only stored names reach the disk');
check(preg_match('/^'.Fonts::FILE_PATTERN.'$/', 'outfit.woff2') === 1 && preg_match('/^'.Fonts::FILE_PATTERN.'$/', '../LICENSES.txt') === 0, 'only bundled font files are served');
foreach (Fonts::BUNDLED as $key => [, , , $files]) {
    foreach ($files as [$file]) {
        check(is_file(__DIR__.'/../resources/fonts/'.$file), "font $key ships its file $file");
    }
}

if ($failures) {
    echo count($failures)." checks failed\n";
    exit(1);
}
echo "All checks passed\n";
