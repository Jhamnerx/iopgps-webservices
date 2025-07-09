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
        Schema::table('log_summaries', function (Blueprint $table) {
            // Modificar el campo hour para permitir formato HH:MM (5 caracteres)
            $table->string('hour', 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_summaries', function (Blueprint $table) {
            // Revertir al tamaño original de 2 caracteres
            $table->string('hour', 2)->change();
        });
    }
};
