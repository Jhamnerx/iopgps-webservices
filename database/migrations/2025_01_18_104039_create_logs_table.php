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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number');
            $table->string('service_name')->nullable();
            $table->string('imei')->nullable();
            $table->string('method');
            $table->dateTime('date')->nullable();
            $table->dateTime('fecha_hora_posicion')->nullable();
            $table->text('request');
            $table->text('response');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
