<?php

declare(strict_types=1);

namespace Simplify\Themes\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The themes, the panel's choice and each person's own choice. Read once per request; every theme comes out through
 * Theme::clean, so what reaches a page is always checked.
 */
final class Store
{
    public const THEMES = 'simplify_themes';

    public const SETTINGS = 'simplify_theme_settings';

    public const CHOICES = 'simplify_theme_users';

    /** @var list<array{id: int, name: string, data: array<string, mixed>}>|null */
    private static ?array $themes = null;

    /** @var array{default: ?int, pick: bool, status: bool}|null */
    private static ?array $settings = null;

    /** @return list<array{id: int, name: string, data: array<string, mixed>}> */
    public static function themes(): array
    {
        if (self::$themes !== null) {
            return self::$themes;
        }
        $fonts = Theme::fontKeys(self::uploadedFonts());
        $images = array_column(Files::all('image'), 'id');

        return self::$themes = DB::table(self::THEMES)->orderBy('id')->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'data' => Theme::clean((array) json_decode((string) $row->data, true), $fonts, $images),
        ])->all();
    }

    /** @return array{id: int, name: string, data: array<string, mixed>}|null */
    public static function find(?int $id): ?array
    {
        foreach ($id === null ? [] : self::themes() as $theme) {
            if ($theme['id'] === $id) {
                return $theme;
            }
        }

        return null;
    }

    /** Saves a theme (a new one without $id) and gives its id. @param array<string, mixed> $data */
    public static function save(?int $id, string $name, array $data): int
    {
        $clean = Theme::clean($data, Theme::fontKeys(self::uploadedFonts()), array_column(Files::all('image'), 'id'));
        $row = ['name' => $name, 'data' => json_encode($clean, JSON_THROW_ON_ERROR), 'updated_at' => now()];
        if ($id !== null && DB::table(self::THEMES)->where('id', $id)->update($row) > 0) {
            self::flush();

            return $id;
        }
        $id = (int) DB::table(self::THEMES)->insertGetId($row + ['created_at' => now()]);
        self::flush();

        return $id;
    }

    public static function delete(int $id): void
    {
        DB::table(self::THEMES)->where('id', $id)->delete();
        DB::table(self::CHOICES)->where('theme_id', $id)->delete();
        if (self::settings()['default'] === $id) {
            self::saveSettings(null, self::settings()['pick']);
        }
        self::flush();
    }

    /** @return array{default: ?int, pick: bool, status: bool} */
    public static function settings(): array
    {
        if (self::$settings === null) {
            $row = DB::table(self::SETTINGS)->where('id', 1)->first();
            self::$settings = ['default' => $row?->default_theme_id !== null ? (int) $row->default_theme_id : null, 'pick' => (bool) ($row->let_people_pick ?? false), 'status' => (bool) ($row->status_page ?? false)];
        }

        return self::$settings;
    }

    /** $default null is Simplify's own look. $status null keeps whether the status page wears the default theme. */
    public static function saveSettings(?int $default, bool $pick, ?bool $status = null): void
    {
        $row = ['default_theme_id' => $default, 'let_people_pick' => $pick, 'updated_at' => now()];
        if ($status !== null) {
            $row['status_page'] = $status;
        }
        DB::table(self::SETTINGS)->updateOrInsert(['id' => 1], $row);
        self::flush();
    }

    /** The theme a person picked: an id, 0 for Simplify's own look, null for the panel's choice. */
    public static function choice(int $userId): ?int
    {
        $value = DB::table(self::CHOICES)->where('user_id', $userId)->value('theme_id');

        return $value === null ? null : (int) $value;
    }

    public static function saveChoice(int $userId, ?int $themeId): void
    {
        if ($themeId === null) {
            DB::table(self::CHOICES)->where('user_id', $userId)->delete();

            return;
        }
        DB::table(self::CHOICES)->updateOrInsert(['user_id' => $userId], ['theme_id' => $themeId, 'updated_at' => now()]);
    }

    /**
     * The theme this person sees: their own pick while people may pick, otherwise the panel's. Null is Simplify's own
     * look, so nothing is added to the page. Never fails: a missing table (a panel that is still installing) is no theme.
     *
     * @return array{id: int, name: string, data: array<string, mixed>}|null
     */
    public static function active(?User $user): ?array
    {
        try {
            $settings = self::settings();
            if ($user !== null && $settings['pick'] && ($choice = self::choice((int) $user->id)) !== null) {
                return $choice === 0 ? null : (self::find($choice) ?? self::find($settings['default']));
            }

            return self::find($settings['default']);
        } catch (Throwable) {
            return null;
        }
    }

    /** The ready-made themes, once: on the first visit of the Themes page of a new panel. */
    public static function addReadyMade(): void
    {
        if (DB::table(self::THEMES)->exists()) {
            return;
        }
        foreach (Presets::ready() as $name => $data) {
            self::save(null, $name, $data);
        }
    }

    /** @return array<int, string> id => name of the uploaded fonts */
    public static function uploadedFonts(): array
    {
        return array_column(Files::all('font'), 'name', 'id');
    }

    public static function flush(): void
    {
        self::$themes = null;
        self::$settings = null;
    }
}
