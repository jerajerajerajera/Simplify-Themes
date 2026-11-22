<?php

declare(strict_types=1);

namespace Simplify\Themes\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Simplify\Themes\Support\Store;

/**
 * "My theme" in the account menu: a person picks one of the panel's themes for themselves, while a root admin lets
 * people pick (Themes page). "Panel default" follows the admin's choice.
 */
final class MyTheme extends Page
{
    private const T = 'simplify-themes::themes.';

    protected static ?string $slug = 'my-theme';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'simplify-themes::my-theme';

    /** "default", "simplify" or a theme id */
    public string $choice = 'default';

    public static function canAccess(): bool
    {
        try {
            return user() !== null && Store::settings()['pick'];
        } catch (\Throwable) {
            return false;
        }
    }

    public function getTitle(): string
    {
        return __(self::T.'my_theme');
    }

    public function getSubheading(): string
    {
        return __(self::T.'my_theme_help');
    }

    public function mount(): void
    {
        $choice = Store::choice((int) user()->id);
        $this->choice = match (true) {
            $choice === null => 'default',
            $choice === 0 => 'simplify',
            default => (string) $choice,
        };
    }

    /** @return array<string, string> value => label */
    public function options(): array
    {
        $default = Store::find(Store::settings()['default']);
        $options = [
            'default' => __(self::T.'panel_default', ['name' => $default['name'] ?? 'Simplify']),
            'simplify' => 'Simplify',
        ];
        foreach (Store::themes() as $theme) {
            $options[(string) $theme['id']] = $theme['name'];
        }

        return $options;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('save')->label(__(self::T.'save'))->icon('tabler-device-floppy')->action('save')->keyBindings(['mod+s'])];
    }

    public function save(): void
    {
        if (! array_key_exists($this->choice, $this->options())) {
            $this->choice = 'default';
        }
        Store::saveChoice((int) user()->id, match ($this->choice) {
            'default' => null,
            'simplify' => 0,
            default => (int) $this->choice,
        });
        Notification::make()->title(__(self::T.'saved'))->success()->send();
        // The new look is in the head of the page: load it again.
        $this->redirect(self::getUrl(), navigate: false);
    }
}
