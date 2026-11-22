<?php

declare(strict_types=1);

namespace Simplify\Themes;

use App\Models\User;
use Simplify\Core\Addons\Addon;
use Simplify\Core\Addons\Button;
use Simplify\Themes\Filament\Admin\Pages\Themes;
use Simplify\Themes\Filament\App\Pages\MyTheme;

/**
 * How Themes shows up inside Simplify. Only loaded when Simplify asks for its add-ons, so Themes also works without
 * Simplify.
 */
final class ThemesAddon extends Addon
{
    public function id(): string
    {
        return SimplifyThemesPlugin::ID;
    }

    public function name(): string
    {
        return 'Themes';
    }

    public function description(): string
    {
        return __('simplify-themes::themes.description');
    }

    public function icon(): string
    {
        return 'tabler-palette';
    }

    public function settingsUrl(): ?string
    {
        return Themes::canAccess() ? Themes::getUrl(panel: 'admin') : null;
    }

    /** "My theme" in Simplify's account menu, while people may pick (Simplify 1.5.5+, add-on API 4). */
    public function accountPages(User $user): array
    {
        return MyTheme::canAccess()
            ? [new Button(__('simplify-themes::themes.my_theme'), MyTheme::getUrl(panel: 'app'), 'tabler-palette')]
            : [];
    }
}
