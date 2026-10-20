<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug');
            $table->string('migration');
            $table->unsignedInteger('batch')->default(1);
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();

            $table->unique(['plugin_slug', 'migration']);
            $table->index('plugin_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_migrations');
    }
};
