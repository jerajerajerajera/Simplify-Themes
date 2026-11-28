<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Whether the public status page wears the default theme too (Simplify 2.0.0-beta.16+, add-on API 11).
        if (Schema::hasTable('simplify_theme_settings') && ! Schema::hasColumn('simplify_theme_settings', 'status_page')) {
            Schema::table('simplify_theme_settings', function (Blueprint $table) {
                $table->boolean('status_page')->default(false);
            });
        }
    }

    public function down(): void
    {
        // Uninstalling keeps the setting, like every theme.
    }
};
