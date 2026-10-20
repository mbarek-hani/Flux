<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('session_id', 64)->index();

            // Page details
            $table->string('url', 2048);
            $table->string('path', 1024);
            $table->string('title')->nullable();

            // Referrer & Campaign
            $table->string('referrer', 2048)->nullable();
            $table->string('referrer_domain')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            // Device & Browser
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('operating_system')->nullable();
            $table->enum('device', ['desktop', 'mobile', 'tablet', 'unknown'])->default('unknown');

            // Location
            $table->char('country_code', 2)->nullable();
            $table->string('country_name')->nullable();

            // Metrics
            $table->unsignedSmallInteger('session_duration')->nullable();
            $table->boolean('is_bounce')->default(true);
            $table->boolean('is_new_session')->default(true);
            $table->timestamp('created_at')->useCurrent()->index();

            // Composite indexes for fast analytics queries
            $table->index(['site_id', 'created_at']);
            $table->index(['site_id', 'path', 'created_at']);
            $table->index(['site_id', 'country_code']);
            $table->index(['site_id', 'device']);
            $table->index(['site_id', 'referrer_domain']);
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
