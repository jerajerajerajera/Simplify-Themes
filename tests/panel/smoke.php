<?php

// Loads Simplify Themes inside a real Pelican panel and goes through what it does.
// Run from the panel folder after `php artisan p:plugin:install simplify-themes`. The Livewire steps need the
// panel's development packages (PHPUnit), as in CI; on a production panel those steps fail on a missing PHPUnit.
// php plugins/simplify-themes/tests/panel/smoke.php
use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserCreationService;
use Filament\Facades\Filament;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Simplify\Themes\Filament\Admin\Pages\Themes;
use Simplify\Themes\Filament\App\Pages\MyTheme;
use Simplify\Themes\Support\Files;
use Simplify\Themes\Support\Fonts;
use Simplify\Themes\Support\Store;
use Simplify\Themes\ThemesAddon;

define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';

// Each step gets a fresh application, like a real request.
function boot(): Application
{
    $app = require getcwd().'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    // Laravel's console handler would print an uncaught error and still exit 0, which CI reads as a pass.
    set_exception_handler(function (Throwable $e): void {
        echo 'FAIL setup: '.get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
        exit(1);
    });
    Store::flush();

    return $app;
}

$failed = false;
function step(string $what, callable $run): void
{
    global $failed;
    try {
        $run(boot());
        echo "PASS $what\n";
    } catch (Throwable $e) {
        $failed = true;
        echo "FAIL $what: ".get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    }
}
function visit(?User $user, string $url): TestResponse
{
    $client = new class(app())
    {
        use InteractsWithAuthentication, InteractsWithSession, MakesHttpRequests;

        public function __construct(public $app) {}
    };

    return $user ? $client->actingAs($user)->get($url) : $client->get($url);
}
function must(bool $ok, string $why): void
{
    if (! $ok) {
        throw new RuntimeException($why);
    }
}
function admin(): void
{
    Filament::setCurrentPanel(Filament::getPanel('admin'));
}

boot();
// Pelican has no root_admin column: root admin is a role.
// Made the way Pelican makes people, not with a factory: a real panel has no Faker.
function person(string $name): User
{
    return User::where('email', "$name@example.com")->first()
        ?? app(UserCreationService::class)->handle(['email' => "$name@example.com", 'username' => $name, 'password' => Str::random(32)]);
}
$admin = person('smoke-themes');
$admin->syncRoles(Role::getRootAdmin());
$member = person('smoke-themes-member');
$simplify = class_exists('Simplify\Core\SimplifyPlugin');

step('the Themes page renders for a root admin and adds the ready-made themes once', function () use ($admin) {
    $url = Themes::getUrl(panel: 'admin');
    $response = visit($admin, $url);
    must($response->getStatusCode() === 200, 'HTTP '.$response->getStatusCode().' at '.$url);
    $html = $response->getContent();
    // P-7: with Simplify the page never scrolls as a whole; P-38: nothing saves by itself.
    must(str_contains($html, 's-fit') && str_contains($html, 'data-simplify-unsaved="data"'), 'the editor is not s-fit or has no unsaved guard');
    // TH-B4: the fonts and pictures are no argument of x-data, so an upload does not start the editor again.
    must((bool) preg_match('/x-data="simplifyThemes\((true|false)\)"/', $html), 'the editor\'s x-data holds more than the core flag');
    must(DB::table(Store::THEMES)->count() === 4, 'the ready-made themes were not added: '.DB::table(Store::THEMES)->count());
    visit($admin, $url);
    must(DB::table(Store::THEMES)->count() === 4, 'the ready-made themes were added twice');
    // Nothing from another site: the fonts come from the panel itself.
    must(! str_contains($html, 'fonts.googleapis.com') && ! str_contains($html, 'fonts.bunny.net/css?family=Outfit'), 'the editor loads fonts from another site');
});

step('the Themes page is closed to other users', function () use ($member) {
    must(visit($member, Themes::getUrl(panel: 'admin'))->getStatusCode() !== 200, 'a member can open the Themes page');
    must(! Themes::canAccess(), 'canAccess() is true without a user');
});

step('a change saves only with Save, checked value by value', function () use ($admin) {
    admin();
    $page = Livewire::actingAs($admin)->test(Themes::class);
    $id = $page->get('themeId');
    $page->set('data.theme.light.accent', '#ff0000')->set('data.theme.dark.bg', 'red;}body{display:none')->set('data.theme.radius', 99);
    Store::flush();
    must(Store::find($id)['data']['light']['accent'] !== '#ff0000', 'the change was stored before Save');
    $page->call('save')->assertHasNoErrors();
    Store::flush();
    $theme = Store::find($id)['data'];
    must($theme['light']['accent'] === '#ff0000', 'Save did not store the accent');
    must($theme['dark']['bg'] === '#101012' && $theme['radius'] === 22, 'an unchecked value was stored: '.json_encode([$theme['dark']['bg'], $theme['radius']]));
    $page->set('data.name', '')->call('save')->assertHasErrors(['data.name']);
});

step('switching themes with unsaved changes asks to save first', function () use ($admin) {
    admin();
    $page = Livewire::actingAs($admin)->test(Themes::class);
    $before = $page->get('themeId');
    $other = collect(Store::themes())->firstWhere('id', '!=', $before)['id'];
    $page->set('data.theme.light.accent', '#00ff00')->call('pick', $other);
    must($page->get('themeId') === $before && $page->get('data.theme.light.accent') === '#00ff00', 'the changes were dropped without asking');
    $count = DB::table(Store::THEMES)->count();
    $page->callAction('newTheme');
    must(DB::table(Store::THEMES)->count() === $count, 'New theme ran over unsaved changes');
});

step('the editor says when the theme it edits is not the one people see', function () use ($admin) {
    admin();
    $page = Livewire::actingAs($admin)->test(Themes::class);
    $default = Store::settings()['default'];
    $other = collect(Store::themes())->firstWhere('id', '!=', $default)['id'];
    must(! str_contains((string) $page->instance()->getSubheading(), 'Not in use'), 'the default theme is called not in use');
    $page->call('pick', $other);
    must(str_contains((string) $page->instance()->getSubheading(), 'Not in use'), 'a theme people do not see is not marked');
});

step('the default theme is on every page, login included; Simplify\'s look adds nothing', function () use ($admin) {
    admin();
    Store::saveSettings(null, false);
    must(! str_contains(visit(null, '/login')->getContent(), 'id="simplify-themes"'), "Simplify's own look adds a style block");
    $page = Livewire::actingAs($admin)->test(Themes::class);
    $page->callAction('makeDefault');
    Store::flush();
    must(Store::settings()['default'] === $page->get('themeId'), 'Make default did not set the default');
    // Livewire::actingAs signed the admin in: a guest sees the login page, a signed-in admin is sent on from it.
    app('auth')->forgetGuards();
    $response = visit(null, '/login');
    $login = $response->getContent();
    must(str_contains($login, '<style id="simplify-themes">') && str_contains($login, '--ps-surface:'), 'the login page has no theme (HTTP '.$response->getStatusCode().')');
    $app = visit($admin, Filament::getPanel('app')->getUrl());
    if ($app->isRedirect()) {
        $app = visit($admin, (string) $app->headers->get('Location'));
    }
    must(str_contains($app->getContent(), '<style id="simplify-themes">'), 'the app panel has no theme');
});

step('a font is recognised by its bytes and served with its own type', function () {
    $bytes = file_get_contents(__DIR__.'/../../resources/fonts/outfit.woff2');
    $id = Files::storeFont(UploadedFile::fake()->createWithContent('harbour.woff2', $bytes), 'Harbour Sans');
    must((Files::all('font')[0]['name'] ?? null) === 'Harbour Sans', 'the font was not stored');
    $file = collect(Files::all('font'))->firstWhere('id', $id)['file'];
    $response = visit(null, '/simplify-themes/file/'.$file);
    must($response->getStatusCode() === 200 && $response->headers->get('Content-Type') === 'font/woff2' && $response->headers->get('X-Content-Type-Options') === 'nosniff', 'the font is not served right: '.$response->getStatusCode().' '.$response->headers->get('Content-Type'));
    foreach (['evil.ttf' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'.str_repeat(' ', 64), 'cut.woff2' => substr($bytes, 0, 2000)] as $name => $content) {
        try {
            Files::storeFont(UploadedFile::fake()->createWithContent($name, $content), 'Evil');
            must(false, "$name was accepted");
        } catch (RuntimeException $e) {
            must($e->getMessage() === 'font_not_valid', "$name: ".$e->getMessage());
        }
    }
    must(count(Files::all('font')) === 1, 'a refused font left a row');
    must(visit(null, '/simplify-themes/file/../../.env')->getStatusCode() === 404 && visit(null, '/simplify-themes/font/LICENSES.txt')->getStatusCode() === 404, 'a path outside the stored files is served');
    must(visit(null, '/simplify-themes/font/outfit.woff2')->headers->get('Content-Type') === 'font/woff2', 'a bundled font is not served');
});

step('a picture is drawn again as WebP; anything else is refused', function () {
    $image = imagecreatetruecolor(3000, 1500);
    imagefill($image, 0, 0, imagecolorallocate($image, 20, 90, 120));
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    // A PNG with something hidden after its end: drawing it again drops it.
    $id = Files::storeImage(UploadedFile::fake()->createWithContent('harbour.png', $png.'<?php echo 1; ?>'));
    $row = collect(Files::all('image'))->firstWhere('id', $id);
    must(str_ends_with($row['file'], '.webp'), 'the picture is not stored as WebP');
    $stored = Storage::disk('local')->get(Files::DIRECTORY.'/'.$row['file']);
    $size = getimagesizefromstring($stored);
    must($size[0] === 2560 && $size[1] === 1280 && ! str_contains($stored, '<?php'), 'the picture was not resized or still holds the hidden text');
    must(visit(null, '/simplify-themes/file/'.$row['file'])->headers->get('Content-Type') === 'image/webp', 'the picture is not served as WebP');
    try {
        Files::storeImage(UploadedFile::fake()->createWithContent('x.png', '<svg onload="alert(1)"/>'));
        must(false, 'an SVG named .png was accepted');
    } catch (RuntimeException $e) {
        must($e->getMessage() === 'image_not_valid', $e->getMessage());
    }
});

step('a file a theme uses cannot be removed; an unused one can', function () {
    $font = Files::all('font')[0];
    $theme = Store::themes()[0];
    Store::save($theme['id'], $theme['name'], ['font' => Fonts::uploadedKey($font['id'])] + $theme['data']);
    must(! Files::remove($font['id']), 'a font in use was removed');
    Store::save($theme['id'], $theme['name'], ['font' => 'inter'] + $theme['data']);
    must(Files::remove($font['id']) && Files::all('font') === [], 'an unused font was not removed');
});

step('export and import give the same theme back', function () use ($admin) {
    admin();
    $page = Livewire::actingAs($admin)->test(Themes::class);
    $theme = $page->get('data.theme');
    $json = json_encode(['simplify-theme' => 1, 'name' => 'Imported', 'theme' => $theme]);
    $count = DB::table(Store::THEMES)->count();
    $page->callAction('import', data: ['file' => UploadedFile::fake()->createWithContent('theme.json', $json)]);
    Store::flush();
    $imported = collect(Store::themes())->firstWhere('name', 'Imported');
    must(DB::table(Store::THEMES)->count() === $count + 1 && $imported !== null, 'the import did not add a theme');
    $expect = $theme;
    $expect['image'] = $expect['image_dark'] = null;
    foreach (['bg', 'bg_dark'] as $key) {
        if ($expect[$key] === 'image') {
            $expect[$key] = 'plain';
        }
    }
    must($imported['data'] == $expect, 'the imported theme differs');
});

step('people can pick a theme only while the admin lets them', function () use ($member) {
    Store::saveSettings(Store::settings()['default'], false);
    must(visit($member, MyTheme::getUrl(panel: 'app'))->getStatusCode() !== 200, 'My theme opens while people may not pick');
    Store::saveSettings(Store::settings()['default'], true);
    $mine = visit($member, MyTheme::getUrl(panel: 'app'));
    must($mine->getStatusCode() === 200, 'My theme does not open while people may pick');
    // TH-B5: "Panel default" shows the default theme's own colours, not the same swatch as Simplify.
    $default = Store::find(Store::settings()['default']);
    preg_match('/<label class="st-mine-row">\s*<input[^>]*value="default".*?<\/label>/s', $mine->getContent(), $row);
    must($default === null || str_contains($row[0] ?? '', $default['data']['light']['accent']), 'Panel default does not show the default theme\'s colours');
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $pick = collect(Store::themes())->firstWhere('id', '!=', Store::settings()['default']);
    Livewire::actingAs($member)->test(MyTheme::class)->set('choice', (string) $pick['id'])->call('save');
    Store::flush();
    must(Store::active($member)['id'] === $pick['id'], 'the pick did not stick');
    Livewire::actingAs($member)->test(MyTheme::class)->set('choice', 'simplify')->call('save');
    must(Store::active($member) === null, "Simplify's look was not picked");
    Livewire::actingAs($member)->test(MyTheme::class)->set('choice', '999999')->call('save');
    Store::flush();
    must(Store::choice($member->id) === null, 'an unknown theme was stored as a pick');
    Store::saveSettings(Store::settings()['default'], false);
    must(Store::active($member)['id'] === Store::settings()['default'], 'a pick still counts after the admin closed picking');
});

step('the add-on is tagged and offers My theme while people may pick', function () use ($member) {
    if (! class_exists('Simplify\Core\Addons\Addon')) {
        require __DIR__.'/stubs/Addon.php';
    }
    $tagged = array_map(fn ($a) => $a::class, iterator_to_array(app()->tagged('simplify.addons')));
    must(in_array(ThemesAddon::class, $tagged, true), 'the add-on is not tagged');
    $addon = app(ThemesAddon::class);
    auth()->setUser($member);
    must($addon->accountPages($member) === [], 'My theme is offered while people may not pick');
    Store::saveSettings(Store::settings()['default'], true);
    must(count($addon->accountPages($member)) === 1, 'My theme is not offered while people may pick');
    Store::saveSettings(Store::settings()['default'], false);
});

step('simplify-themes:reset brings back Simplify\'s look for everyone', function () {
    Artisan::call('simplify-themes:reset');
    Store::flush();
    must(Store::settings() === ['default' => null, 'pick' => false] && DB::table(Store::THEMES)->count() > 0, 'reset did not work or lost themes');
});

if ($simplify) {
    step('with Simplify the page opens inside Simplify', function () use ($admin) {
        $html = visit($admin, Themes::getUrl(panel: 'admin'))->getContent();
        must(str_contains($html, 'st-core') && str_contains($html, 'ps-admin'), 'the page is not drawn in Simplify');
    });
}

echo $failed ? "Some steps failed\n" : "All steps passed\n";
exit($failed ? 1 : 0);
