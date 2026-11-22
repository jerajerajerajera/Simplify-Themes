<?php

declare(strict_types=1);

namespace Simplify\Themes\Filament\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Simplify\Themes\Support\Files;
use Simplify\Themes\Support\Fonts;
use Simplify\Themes\Support\Presets;
use Simplify\Themes\Support\Store;
use Simplify\Themes\Support\Theme;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin › Simplify › Themes: pick a theme, change it in tabs (Themes, Colours, Background, Fonts, Shapes & console)
 * with a live preview, and Save. Nothing saves by itself (P-38): the page carries data-simplify-unsaved on `data`.
 * Root admins only. With Simplify it opens inside Simplify's Admin page; without it, it is a normal Pelican page.
 */
final class Themes extends Page
{
    private const T = 'simplify-themes::themes.';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-palette';

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'simplify-themes';

    protected string $view = 'simplify-themes::themes';

    public ?int $themeId = null;

    /** @var array<string, mixed> what the editor holds (name, theme, pick); the browser can send anything here, compared for "Changes made" */
    public array $data = ['name' => '', 'theme' => [], 'pick' => false];

    public static function canAccess(): bool
    {
        return (bool) user()?->isRootAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __(self::T.'title');
    }

    /** In the Simplify group of Pelican's menu, with the other Simplify add-ons (Simplify's ADDONS.md, "Admin menu"). */
    public static function getNavigationGroup(): string
    {
        return 'Simplify';
    }

    public function getTitle(): string
    {
        return __(self::T.'title');
    }

    public function getSubheading(): string
    {
        return __(self::T.'subtitle');
    }

    public function mount(): void
    {
        Store::addReadyMade();
        $this->open(Store::settings()['default'] ?? (Store::themes()[0]['id'] ?? 0));
    }

    /** Opens a theme in the editor; the page asks Save or Discard first when there are changes (unsaved.js). */
    public function open(int $id): bool
    {
        $theme = Store::find($id) ?? Store::themes()[0] ?? null;
        if ($theme === null) {
            $id = Store::save(null, __(self::T.'new_name'), Presets::SIMPLIFY);
            $theme = Store::find($id);
        }
        $this->themeId = $theme['id'];
        $this->data = ['name' => $theme['name'], 'theme' => $theme['data'], 'pick' => Store::settings()['pick']];
        $this->resetErrorBag();
        // The editor compares against what was opened, for its own "Changes made" bar without Simplify.
        $this->dispatch('simplify-themes-opened');

        return true;
    }

    /** A theme tile: opens it, unless there are changes to save or discard first. */
    public function pick(int $id): void
    {
        if ($id !== $this->themeId && ! $this->saveFirst()) {
            $this->open($id);
        }
    }

    public function save(): void
    {
        $name = Theme::name($this->data['name'] ?? null);
        if ($name === null) {
            $this->addError('data.name', __(self::T.'name_invalid', ['max' => Theme::NAME_MAX]));

            return;
        }
        $this->themeId = Store::save($this->themeId, $name, (array) ($this->data['theme'] ?? []));
        Store::saveSettings(Store::settings()['default'], (bool) ($this->data['pick'] ?? false));
        $this->open($this->themeId);
        Notification::make()->title(__(self::T.'saved'))->success()->send();
    }

    /** Whether the editor holds something Save has not stored yet. */
    private function changed(): bool
    {
        $stored = Store::find($this->themeId);
        $theme = Theme::clean((array) ($this->data['theme'] ?? []), Theme::fontKeys(Store::uploadedFonts()), array_column(Files::all('image'), 'id'));

        return $stored === null || $stored['name'] !== trim((string) ($this->data['name'] ?? ''))
            || $stored['data'] !== $theme || Store::settings()['pick'] !== (bool) ($this->data['pick'] ?? false);
    }

    /** Actions that leave this theme run only without unsaved changes: nothing is lost without asking (P-38). */
    private function saveFirst(): bool
    {
        if (! $this->changed()) {
            return false;
        }
        Notification::make()->title(__(self::T.'save_first'))->warning()->send();

        return true;
    }

    /** @return list<array{id: int, name: string, data: array<string, mixed>, default: bool}> */
    public function themes(): array
    {
        $default = Store::settings()['default'];

        return array_map(fn (array $t) => $t + ['default' => $t['id'] === $default], Store::themes());
    }

    /** What the editor needs to draw fonts and pictures. @return array<string, mixed> */
    public function library(): array
    {
        $fonts = [];
        foreach (Fonts::BUNDLED as $key => [$label, $family, $generic, $files]) {
            $fonts[] = ['key' => $key, 'label' => $label, 'stack' => Fonts::stack($key, []), 'note' => __(self::T.'font_'.$key), 'mono' => $generic === 'mono',
                'faces' => array_map(fn (array $f) => ['family' => $family, 'url' => url('/simplify-themes/font/'.$f[0]), 'weight' => $f[1], 'format' => 'woff2'], $files)];
        }
        $uploadedFonts = [];
        $images = [];
        foreach (Files::all() as $file) {
            if ($file['kind'] === 'font') {
                $uploadedFonts[] = ['id' => $file['id'], 'key' => Fonts::uploadedKey($file['id']), 'label' => $file['name'], 'stack' => Fonts::stack(Fonts::uploadedKey($file['id']), [$file['id'] => $file['name']]),
                    'size' => $file['size'], 'faces' => [['family' => Fonts::uploadedFamily($file['name']), 'url' => Files::url($file['file']), 'weight' => '100 900', 'format' => str_ends_with($file['file'], '.ttf') ? 'truetype' : 'woff2']]];
            } else {
                $images[] = ['id' => $file['id'], 'name' => $file['name'], 'url' => Files::url($file['file']), 'size' => $file['size']];
            }
        }

        return ['fonts' => $fonts, 'uploaded' => $uploadedFonts, 'images' => $images, 'simplify' => Presets::SIMPLIFY];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('newTheme')->label(__(self::T.'new'))->icon('tabler-plus')
                    ->action(fn () => $this->saveFirst() || $this->open(Store::save(null, __(self::T.'new_name'), Presets::SIMPLIFY))),
                Action::make('duplicate')->label(__(self::T.'duplicate'))->icon('tabler-copy')
                    ->action(fn () => $this->saveFirst() || $this->open(Store::save(null, Str::limit(__(self::T.'copy_of', ['name' => $this->data['name']]), Theme::NAME_MAX, ''), (array) $this->data['theme']))),
                Action::make('makeDefault')->label(__(self::T.'make_default'))->icon('tabler-star')
                    ->hidden(fn () => Store::settings()['default'] === $this->themeId)
                    ->action(function () {
                        if ($this->saveFirst()) {
                            return;
                        }
                        Store::saveSettings($this->themeId, Store::settings()['pick']);
                        Notification::make()->title(__(self::T.'default_set', ['name' => $this->data['name']]))->success()->send();
                    }),
                Action::make('useSimplify')->label(__(self::T.'use_simplify'))->icon('tabler-sparkles')
                    ->hidden(fn () => Store::settings()['default'] === null)
                    ->action(function () {
                        Store::saveSettings(null, Store::settings()['pick']);
                        Notification::make()->title(__(self::T.'simplify_set'))->success()->send();
                    }),
                Action::make('export')->label(__(self::T.'export'))->icon('tabler-download')
                    ->action(fn () => $this->export()),
                Action::make('import')->label(__(self::T.'import'))->icon('tabler-upload')
                    ->schema([FileUpload::make('file')->label(__(self::T.'import_file'))->acceptedFileTypes(['application/json', 'text/plain'])->maxSize(64)->storeFiles(false)->required()])
                    ->action(fn (array $data) => $this->saveFirst() || $this->import($data['file'] ?? null)),
                Action::make('reset')->label(__(self::T.'reset'))->icon('tabler-restore')
                    ->action(fn () => $this->data['theme'] = Presets::SIMPLIFY),
                Action::make('delete')->label(__(self::T.'delete'))->icon('tabler-trash')->color('danger')
                    ->requiresConfirmation()->modalHeading(fn () => __(self::T.'delete_heading', ['name' => $this->data['name']]))
                    ->modalDescription(__(self::T.'delete_help'))
                    ->action(function () {
                        Store::delete((int) $this->themeId);
                        $this->open(Store::settings()['default'] ?? (Store::themes()[0]['id'] ?? 0));
                    }),
            ])->label(__(self::T.'more'))->icon('tabler-dots')->iconButton()->color('gray'),
            // Save last, as on every Simplify page (ADDONS.md, "Page head").
            Action::make('save')->label(__(self::T.'save'))->icon('tabler-device-floppy')->action('save')->keyBindings(['mod+s']),
        ];
    }

    public function uploadFontAction(): Action
    {
        return Action::make('uploadFont')->label(__(self::T.'font_upload'))->icon('tabler-upload')->color('gray')
            ->modalHeading(__(self::T.'font_upload'))->modalDescription(__(self::T.'font_upload_help'))
            ->schema([
                TextInput::make('name')->label(__(self::T.'font_name'))->required()->maxLength(40)->regex(Fonts::NAME_PATTERN),
                FileUpload::make('file')->label(__(self::T.'font_file'))->maxSize(intdiv(Files::FONT_MAX, 1024))->storeFiles(false)->required(),
            ])
            ->action(function (array $data) {
                $file = $data['file'] ?? null;
                if (! $file instanceof TemporaryUploadedFile) {
                    return;
                }
                try {
                    $id = Files::storeFont($file, trim((string) $data['name']));
                    $this->data['theme']['font'] = Fonts::uploadedKey($id);
                } catch (RuntimeException $e) {
                    Notification::make()->title(__(self::T.$e->getMessage()))->danger()->send();
                }
            });
    }

    public function uploadImageAction(): Action
    {
        return Action::make('uploadImage')->label(__(self::T.'image_upload'))->icon('tabler-upload')->color('gray')
            ->modalHeading(__(self::T.'image_upload'))->modalDescription(__(self::T.'image_upload_help'))
            ->schema([FileUpload::make('file')->hiddenLabel()->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->maxSize(intdiv(Files::IMAGE_MAX, 1024))->storeFiles(false)->required()])
            ->action(function (array $data, array $arguments) {
                $file = $data['file'] ?? null;
                if (! $file instanceof TemporaryUploadedFile) {
                    return;
                }
                try {
                    $id = Files::storeImage($file);
                    $this->data['theme'][($arguments['dark'] ?? false) ? 'image_dark' : 'image'] = $id;
                    $this->data['theme']['bg'] = 'image';
                } catch (RuntimeException $e) {
                    Notification::make()->title(__(self::T.$e->getMessage()))->danger()->send();
                }
            });
    }

    public function removeFileAction(): Action
    {
        return Action::make('removeFile')->label(__(self::T.'remove'))->icon('tabler-trash')->color('danger')->iconButton()
            ->requiresConfirmation()->modalHeading(__(self::T.'remove_heading'))
            ->action(function (array $arguments) {
                if (! Files::remove((int) ($arguments['id'] ?? 0))) {
                    Notification::make()->title(__(self::T.'remove_in_use'))->warning()->send();
                }
            });
    }

    private function export(): StreamedResponse
    {
        $json = json_encode(['simplify-theme' => 1, 'name' => $this->data['name'], 'theme' => $this->data['theme']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, (Str::slug((string) $this->data['name']) ?: 'theme').'.json', ['Content-Type' => 'application/json']);
    }

    /** A theme from an exported file: checked like any other (Theme::clean), uploads it points at are dropped. */
    private function import(mixed $file): bool
    {
        $json = $file instanceof TemporaryUploadedFile ? json_decode((string) $file->get(), true) : null;
        $name = is_array($json) ? Theme::name($json['name'] ?? null) : null;
        if (! is_array($json) || ($json['simplify-theme'] ?? null) !== 1 || $name === null || ! is_array($json['theme'] ?? null)) {
            Notification::make()->title(__(self::T.'import_invalid'))->danger()->send();

            return false;
        }
        $theme = $json['theme'];
        unset($theme['image'], $theme['image_dark']);
        foreach (Theme::FONT_SLOTS as $slot) {
            if (is_string($theme[$slot] ?? null) && Fonts::uploadedId($theme[$slot]) !== null) {
                unset($theme[$slot]);
            }
        }
        $this->open(Store::save(null, $name, $theme));
        Notification::make()->title(__(self::T.'imported', ['name' => $name]))->success()->send();

        return true;
    }
}
