<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();

            $table->string('session_id', 64)->index();
            $table->string('type', 100)->index();
            $table->string('name')->nullable();
            $table->json('data')->nullable();
            $table->string('path', 1024)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['site_id', 'type', 'created_at']);
            $table->index(['site_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
