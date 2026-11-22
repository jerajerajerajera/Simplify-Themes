<?php

declare(strict_types=1);

namespace Simplify\Themes\Console\Commands;

use Illuminate\Console\Command;
use Simplify\Themes\Support\Store;

/** The way back when a theme made the panel hard to read: everyone sees Simplify's own look again. Themes stay. */
final class ResetTheme extends Command
{
    protected $signature = 'simplify-themes:reset';

    protected $description = "Show Simplify's own look to everyone again; your themes are kept";

    public function handle(): int
    {
        Store::saveSettings(null, false);
        $this->info("Everyone sees Simplify's own look again. Your themes are kept.");

        return self::SUCCESS;
    }
}
