<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('version', 20)->default('1.0.0');
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('license')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('configuration')->nullable();
            $table->json('manifest')->nullable();
            $table->string('status', 20)->default('discovered');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
