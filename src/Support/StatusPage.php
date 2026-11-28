<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

use Simplify\Core\Addons\Addon;
use Throwable;

/**
 * The default theme on the core's public status page (/status and its own address), while the admin turned "Status
 * page" on. Only the default theme: visitors are not signed in. Needs the core's status page hook (add-on API 11); on
 * an older core the switch is hidden and nothing changes.
 */
final class StatusPage
{
    public static function supported(): bool
    {
        return class_exists(Addon::class) && method_exists(Addon::class, 'statusPageHead');
    }

    public static function head(): string
    {
        $theme = self::theme();

        return $theme === null ? '' : '<style id="simplify-themes">'.Head::css($theme, true).'</style>';
    }

    public static function body(): string
    {
        $theme = self::theme();

        return $theme === null ? '' : Effects::tag($theme, Effects::scriptUrl(), true);
    }

    /** @return array<string, mixed>|null the clean default theme, or null while the switch is off */
    private static function theme(): ?array
    {
        try {
            $settings = Store::settings();

            return $settings['status'] ? Store::find($settings['default'])['data'] ?? null : null;
        } catch (Throwable) {
            return null;
        }
    }
}
