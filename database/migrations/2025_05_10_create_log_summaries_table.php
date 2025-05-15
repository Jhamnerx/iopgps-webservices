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
        Schema::create('log_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->date('date');
            $table->string('hour', 2); // Hora del día (00-23)
            $table->string('imei')->nullable();
            $table->string('plate_number')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('total_count')->default(0);
            $table->json('error_samples')->nullable(); // Hasta 5 ejemplos de errores
            $table->json('success_samples')->nullable(); // Muestras de respuestas exitosas para evidencia
            $table->timestamps();

            // Índices para optimizar las consultas
            $table->index('service_name');
            $table->index('date');
            $table->index(['date', 'hour']);
            $table->index('imei');
            $table->index('plate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_summaries');
    }
};
