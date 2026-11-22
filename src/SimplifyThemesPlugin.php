<?php

declare(strict_types=1);

namespace Simplify\Themes;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Simplify\Themes\Filament\Admin\Pages\Themes;
use Simplify\Themes\Filament\App\Pages\MyTheme;

final class SimplifyThemesPlugin implements Plugin
{
    public const ID = 'simplify-themes';

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() === 'admin') {
            $panel->pages([Themes::class]);
        }
        if ($panel->getId() === 'app') {
            $panel->pages([MyTheme::class]);
        }
        // Without Simplify "My theme" is a line in the account menu; with it, an icon in Simplify's account menu (ThemesAddon).
        if (in_array($panel->getId(), ['app', 'server'], true) && ! class_exists('Simplify\Core\SimplifyPlugin')) {
            $panel->userMenuItems([
                Action::make('simplify-themes-mine')->label(fn () => __('simplify-themes::themes.my_theme'))->icon('tabler-palette')
                    ->url(fn () => MyTheme::getUrl(panel: 'app'))
                    ->visible(fn () => MyTheme::canAccess()),
            ]);
        }
    }

    public function boot(Panel $panel): void {}
}
