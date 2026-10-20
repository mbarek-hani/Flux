<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->string('plugin', 100);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['visit_id', 'plugin', 'key']);
            $table->index(['plugin', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_metadata');
    }
};
