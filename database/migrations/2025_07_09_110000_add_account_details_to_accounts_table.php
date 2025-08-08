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
        Schema::table('accounts', function (Blueprint $table) {
            // Campos adicionales para la información de la cuenta
            $table->string('contactUser')->nullable()->after('account');
            $table->string('contactTel')->nullable()->after('contactUser');
            $table->string('email')->nullable()->after('contactTel');
            $table->text('address')->nullable()->after('email');
            $table->string('renewalContact')->nullable()->after('address');

            // Campo para almacenar datos adicionales
            $table->json('additional_data')->nullable()->after('renewalContact');

            // Campo para rastrear la última actualización
            $table->dateTime('details_last_updated')->nullable()->after('additional_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Eliminar campos adicionales
            $table->dropColumn('contactUser');
            $table->dropColumn('contactTel');
            $table->dropColumn('email');
            $table->dropColumn('address');
            $table->dropColumn('renewalContact');
            $table->dropColumn('additional_data');
            $table->dropColumn('details_last_updated');
        });
    }
};
