<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('identifiant')->unique(); // ex: "ai-analytics"
            $table->string('nom'); // ex: "Analyse IA"
            $table->string('version', 20); // ex: "1.0.0"
            $table->text('description')->nullable();
            $table->string('auteur')->nullable();
            $table->boolean('actif')->default(false);
            $table->json('configuration')->nullable(); // Config spécifique au plugin
            $table->json('metadonnees')->nullable(); // Données du manifest
            $table->timestamp('installe_le')->nullable();
            $table->timestamp('active_le')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
