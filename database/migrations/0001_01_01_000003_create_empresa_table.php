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
        Schema::create('empresa', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('tipo_documento')->nullable();
            $table->text('razon_social');
            $table->text('nombre_comercial')->nullable();
            $table->text('nombre_web')->nullable();
            $table->text('ruc');
            $table->text('direccion')->nullable();
            $table->text('telefono')->nullable();
            $table->text('correo')->nullable();
            $table->text('mail_config')->nullable();
            $table->longText('estilos')->nullable();
            $table->text('extra')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla');
    }
};
