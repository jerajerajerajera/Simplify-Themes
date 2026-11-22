<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uninstalling keeps the tables (down() below), so installing again finds every theme back.
        if (! Schema::hasTable('simplify_themes')) {
            Schema::create('simplify_themes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 40);
                $table->text('data');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('simplify_theme_files')) {
            Schema::create('simplify_theme_files', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 10);
                $table->string('name', 80);
                $table->string('file', 40)->unique();
                $table->unsignedInteger('size');
                $table->timestamp('created_at')->nullable();
            });
        }
        if (! Schema::hasTable('simplify_theme_settings')) {
            Schema::create('simplify_theme_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('default_theme_id')->nullable();
                $table->boolean('let_people_pick')->default(false);
                $table->timestamp('updated_at')->nullable();
            });
        }
        if (! Schema::hasTable('simplify_theme_users')) {
            Schema::create('simplify_theme_users', function (Blueprint $table) {
                $table->unsignedInteger('user_id')->primary();
                $table->unsignedBigInteger('theme_id');
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /** Uninstalling keeps the themes and uploads: installing again brings them back. */
    public function down(): void {}
};
