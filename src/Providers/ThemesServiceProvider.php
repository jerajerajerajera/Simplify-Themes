<?php

declare(strict_types=1);

namespace Simplify\Themes\Providers;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Simplify\Themes\Console\Commands\ResetTheme;
use Simplify\Themes\Http\Controllers\FileController;
use Simplify\Themes\Support\Files;
use Simplify\Themes\Support\Fonts;
use Simplify\Themes\Support\Head;
use Simplify\Themes\ThemesAddon;

final class ThemesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Simplify reads this tag to list its add-ons. Without Simplify nothing reads it and ThemesAddon is never loaded.
        $this->app->tag([ThemesAddon::class], 'simplify.addons');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ResetTheme::class]);
        }

        // Open without login, like the panel logo: the login page wears the theme too (README, "What it sends where").
        Route::get('/simplify-themes/file/{file}', [FileController::class, 'uploaded'])
            ->where('file', Files::PATTERN)->name('simplify-themes.file');
        Route::get('/simplify-themes/font/{file}', [FileController::class, 'bundled'])
            ->where('file', Fonts::FILE_PATTERN)->name('simplify-themes.font');
        Route::get('/simplify-themes/effects.js', [FileController::class, 'script'])->name('simplify-themes.effects');

        // Every page of every panel, login included: Filament's colours first, then the style block at the end of the head.
        Filament::serving(fn () => Head::colours());
        FilamentView::registerRenderHook(PanelsRenderHook::HEAD_END, fn () => Head::style());
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn () => Head::effect());
    }
}
