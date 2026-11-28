<?php

// The checks of a theme that need no panel: every value that reaches a style is checked, the style block only sets
// tokens, and a font upload is recognised by its bytes. Run: php tests/themes.php
declare(strict_types=1);

foreach (['Colour', 'Fonts', 'Presets', 'Theme', 'Gradient', 'Css', 'Effects', 'Files'] as $class) {
    require __DIR__."/../src/Support/$class.php";
}

use Simplify\Themes\Support\Colour;
use Simplify\Themes\Support\Css;
use Simplify\Themes\Support\Effects;
use Simplify\Themes\Support\Files;
use Simplify\Themes\Support\Fonts;
use Simplify\Themes\Support\Gradient;
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
// TH-B11: the panel's grey scale keeps the picked grey's hue but stays a grey, not an accent.
$greys = Colour::greys('#64748b');
check(count($greys) === 11 && preg_match('/^oklch\(0\.705 0\.0[0-4]\d 25\d\./', $greys[400]) === 1, 'a slate grey gives a slate grey scale');
check((float) explode(' ', Colour::greys('#2563eb')[400])[1] <= Colour::GREY_CHROMA, 'a bright colour as grey still gives a grey scale');

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
// TH-B16: text straight on a loud gradient or a picture gets a soft edge in the background's colour; on a calm one nothing.
$loud = Presets::ready()['Ocean night'];
$loud['bg_dark'] = 'gradient';
$loud['grad']['dark']['stops'] = [['c' => '#0b1020', 'p' => 0], ['c' => '#fde047', 'p' => 50], ['c' => '#f97316', 'p' => 100]];
$loud['grad']['dark']['mids'] = [50, 50, 50];
$loudCss = Css::build($clean($loud), $url, [], []);
check(str_contains($loudCss, 'html.dark :is(.ps-admin-side') && str_contains($loudCss, 'text-shadow:0 0 2px '.$loud['dark']['bg']), 'text on a loud dark gradient gets an edge in the dark background colour');
check(! str_contains($loudCss, 'html:not(.dark) :is(.ps-admin-side'), 'a calm light gradient gets no edge');
check(! str_contains($css, 'text-shadow') && ! str_contains($plain, 'text-shadow'), 'Ocean night and a plain background get no edge');
check(str_contains($pic, 'html:not(.dark) :is(.ps-admin-side'), 'text over a picture with little fade gets an edge');
check(str_contains($pic, 'html:root:not(.dark)::before') && str_contains($pic, 'html:root.dark::before') && str_contains($pic, 'blur(8px)'), 'a picture background is drawn behind everything, blurred');
// TH-B6/B7: over a gradient or a picture the Admin page's box is a card, title band included; a plain one stays as the core draws it.
check(str_contains($css, '.fi-main-ctn{background:var(--ps-surface) !important') && str_contains($pic, '.fi-main .fi-header{background:var(--ps-surface) !important'), 'over a gradient or picture the admin page is one card with its title band');
check(! str_contains($plain, '--ps-console-card') && str_contains(Css::build($clean(['console' => 'follow']), $url, [], []), '--ps-console-card:#ffffff'), 'the console card and its command line get one colour');
check(! str_contains($plain, 'fi-main-ctn'), 'over a plain background the admin page stays as Simplify draws it');
// Light and dark each have their own background; a theme from before keeps one for both.
$img = '/simplify-themes/file/'.str_repeat('b', 32).'.webp';
$split = Css::build($clean(['bg' => 'gradient', 'bg_dark' => 'image', 'image' => 3]), $url, [], [3 => $img]);
check(str_contains($split, ':root:root:root:not(.dark){background:linear-gradient(') && ! str_contains($split, ':root:root:root.dark{background:')
    && str_contains($split, 'html:root.dark::before') && ! str_contains($split, 'html:root:not(.dark)::before'), 'light and dark each wear their own background');
check($clean(['bg' => 'gradient'])['bg_dark'] === 'gradient' && $clean(['bg' => 'gradient', 'bg_dark' => 'plain'])['bg_dark'] === 'plain', 'a theme without a dark background keeps its one background for both');
check($clean(['bg' => 'plain', 'bg_dark' => 'image'])['bg_dark'] === 'plain', 'a dark picture background without a picture falls back to plain');
$half = Css::build($clean(['bg' => 'plain', 'bg_dark' => 'gradient']), $url, [], []);
check(str_contains($half, 'html.ps-shell.dark:not(.ps-frame) .fi-layout>.fi-main-ctn{') && ! str_contains($half, 'html.ps-shell:not(.dark)'), 'the admin card follows each mode\'s own background');
check(str_contains($pic, 'rgb(244 244 245 / 0.30)') && str_contains($pic, 'pointer-events:none;background:linear-gradient(rgb(0 0 0 / 0.30)'), 'Fade is a light veil in light mode and a dark one in dark mode');
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

// TH-B12: "In use" means shown. A picture kept in a theme whose background is now a gradient is not in use.
$kept = $clean(['bg' => 'gradient', 'bg_dark' => 'plain', 'image' => 3, 'image_dark' => 3] + Presets::SIMPLIFY);
check(! Files::usedBy($kept, 3, 'image'), 'a picture behind no mode is not in use');
check(Files::usedBy(['bg' => 'image'] + $kept, 3, 'image') && Files::usedBy(['bg_dark' => 'image'] + $kept, 3, 'image'), 'a picture behind light or dark is in use');
check(Files::usedBy(['bg_dark' => 'image', 'image_dark' => null] + $kept, 3, 'image'), 'dark without its own picture uses the light one');
check(Files::usedBy(['head' => Fonts::uploadedKey(7)] + $kept, 7, 'font') && ! Files::usedBy($kept, 7, 'font'), 'an uploaded font is in use in any slot, and only then');
check(preg_match('/^'.Fonts::FILE_PATTERN.'$/', 'outfit.woff2') === 1 && preg_match('/^'.Fonts::FILE_PATTERN.'$/', '../LICENSES.txt') === 0, 'only bundled font files are served');
foreach (Fonts::BUNDLED as $key => [, , , $files]) {
    foreach ($files as [$file]) {
        check(is_file(__DIR__.'/../resources/fonts/'.$file), "font $key ships its file $file");
    }
}

// Gradients: 2 to 6 checked colours, numbers in range, and a seamless repeat
$g = $clean(['bg' => 'gradient', 'grad' => ['dark' => ['type' => 'conic', 'angle' => 720, 'cx' => 150, 'repeat' => true, 'size' => 20, 'wrap' => 1,
    'stops' => [['c' => '#FF0000', 'p' => 0], ['c' => 'red;}', 'p' => 50], ['c' => '#0000ff', 'p' => 80]], 'mids' => [30, 50, 99]]]]);
$dark = $g['grad']['dark'];
check($dark['type'] === 'conic' && $dark['angle'] === 359 && $dark['cx'] === 100 && $dark['wrap'] === 5 && count($dark['stops']) === 2 && $dark['stops'][0]['c'] === '#ff0000', 'gradient values are checked and kept in range');
check(Gradient::css($dark) === 'repeating-conic-gradient(from 359deg at 100% 30%, #ff0000 0%, 4.8%, #0000ff 16%, 16.2%, #ff0000 20%)', 'a repeating gradient blends its last colour back into its first');
check($clean(['grad' => ['light' => ['stops' => [['c' => '#ffffff', 'p' => 0]]]]])['grad']['light'] === Gradient::fromPalette(Presets::SIMPLIFY['light']), 'a gradient needs two colours, or it is the theme\'s own');
check(count($clean(['grad' => ['light' => ['stops' => array_fill(0, 9, ['c' => '#123456', 'p' => 10])]]])['grad']['light']['stops']) === Gradient::MAX, 'at most six colours');
$gCss = Css::build($g, fn ($f) => '/f/'.$f, [], []);
check(str_contains($gCss, ':root:root:root.dark{background:repeating-conic-gradient(') && ! str_contains($gCss, 'red;}'), 'the panel wears the checked gradient');
check(str_contains(Css::build($clean(['bg' => 'gradient']), fn ($f) => '/f/'.$f, [], []), 'linear-gradient(160deg, #f4f4f5 0%, 50%, '), 'a theme without its own gradient keeps the old one');

// The moving background: only known effects, numbers in range, a colour or the theme's own; no code reaches the page
$fx = $clean(['effect' => 'stars', 'fx_amount' => 9999, 'fx_speed' => -5, 'fx_dir' => 400, 'fx_glow' => '30', 'fx_colour' => 'red"><script>']);
check($fx['effect'] === 'stars' && $fx['fx_amount'] === 250 && $fx['fx_speed'] === 10 && $fx['fx_dir'] === 359 && $fx['fx_glow'] === 30, 'effect settings are kept inside their range');
check($fx['fx_colour'] === null && $clean(['fx_colour' => '#ABCDEF'])['fx_colour'] === '#abcdef', 'an effect colour is #rrggbb or follows the theme');
check($clean(['effect' => 'alert(1)'])['effect'] === 'none', 'an unknown effect is no effect');
check(Effects::tag($clean([]), '/x.js') === '' && Effects::css($clean([])) === '', 'without an effect the page gets nothing');
$tag = Effects::tag($fx, '/simplify-themes/effects.js?v=1');
preg_match('/data-fx="([^"]*)"/', $tag, $m);
$read = json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true);
check(substr_count($tag, '<script') === 1 && str_contains($tag, 'src="/simplify-themes/effects.js?v=1"') && ! preg_match('/<script[^>]*>[^<]/', $tag), 'the page gets one script from the panel and no inline code');
check(($read['type'] ?? null) === 'stars' && ($read['light']['col'] ?? null) === Presets::SIMPLIFY['light']['accent'] && ($read['dark']['col'] ?? null) === '#ffffff', 'the canvas reads clean settings with colours for light and dark');
check(Effects::colours($clean(['effect' => 'snow', 'fx_colour' => '#ff0000']), 'dark')['col'] === '#ff0000', 'an own colour wins over the theme');
$types = Theme::CHOICES['effect'];
$own = array_keys(Effects::OWN);
sort($types);
sort($own);
check(array_keys(Effects::START) === Theme::CHOICES['effect'] && $own === $types, 'every effect has a start direction and colours');
$fxCss = Css::build($fx, fn ($f) => '/f/'.$f, [], []);
check(str_contains($fxCss, '#simplify-themes-fx{position:fixed') && substr_count($fxCss, '{') === substr_count($fxCss, '}'), 'an effect adds the canvas layer to the style block');
$js = (string) file_get_contents(__DIR__.'/../'.Effects::SCRIPT);
foreach (Theme::CHOICES['effect'] as $type) {
    check($type === 'none' || str_contains($js, "\n        $type: {"), "the script draws $type");
}
check(str_contains($js, 'prefers-reduced-motion') && str_contains($js, 'visibilitychange') && ! str_contains($js, 'ev'.'al(') && ! preg_match('#https?://#', $js), 'the script respects less motion, pauses when hidden and loads nothing else');

// The admin menu's group headings, its health bar and the phone's bottom bar follow the heading font (Jeroen 28-11).
$heads = Css::build($clean(['head' => 'outfit']), $url, [], []);
check(preg_match('/:is\([^)]*\.ps-host-group-head, \.ps-host-health-bar, \.ps-tabbar\)\{font-family:"Simplify Outfit"/', $heads) === 1, 'menu group headings, health bar and bottom bar wear the heading font');

// TH-S1: the status page wears the default theme. It has no dark class, so both modes go by the visitor's device;
// the list (embed.js) reads --simplify-embed-*.
$ocean = $clean(Presets::ready()['Ocean night']);
$status = Css::status($ocean, $url, [], []);
$darkAt = strpos($status, '@media (prefers-color-scheme:dark){');
check($darkAt !== false && str_contains(substr($status, $darkAt), '--simplify-embed-bg:'.$ocean['dark']['bg'])
    && str_contains(substr($status, 0, $darkAt), '--simplify-embed-bg:'.$ocean['light']['bg']), 'the status page gets the list tokens for light and, by the device, for dark');
check(str_contains($status, '--simplify-embed-card:') && str_contains($status, '--simplify-embed-accent:'.$ocean['light']['accent']) && str_contains($status, '--simplify-embed-radius:'.$ocean['radius'].'px'), 'cards, accent and corners reach the list');
check(! str_contains($status, '.dark') && ! str_contains($status, '--ps-') && ! str_contains($status, '<'), 'the status page block uses no panel classes, no panel tokens and no markup');
check(! preg_match('#url\("https?://#', $status) && str_contains($status, '/simplify-themes/font/'), 'status page fonts come from the panel itself');
$statusGrad = Css::status($clean(['bg' => 'gradient', 'bg_dark' => 'image', 'image' => 3]), $url, [], [3 => $img]);
check(preg_match('/@media not all and \(prefers-color-scheme:dark\)\{[^@]*:root:root\{background:linear-gradient\(/', $statusGrad) === 1
    && preg_match('/@media \(prefers-color-scheme:dark\)\{[^@]*:root:root::before\{[^}]*url\("'.preg_quote($img, '/').'"\)/', $statusGrad) === 1, 'the status page gets the gradient in light and the picture in dark');
check(str_contains(Effects::tag($fx, '/e.js', true), '&quot;media&quot;:true') && ! str_contains(Effects::tag($fx, '/e.js'), 'media'), 'on the status page the effect follows the device, in the panel the dark class');

if ($failures) {
    echo count($failures)." checks failed\n";
    exit(1);
}
echo "All checks passed\n";
