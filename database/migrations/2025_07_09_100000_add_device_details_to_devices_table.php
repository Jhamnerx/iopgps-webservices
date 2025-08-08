<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero añadimos los campos nuevos
        Schema::table('devices', function (Blueprint $table) {
            // Solo añadimos el campo para rastrear la última actualización de detalles
            $table->dateTime('details_last_updated')->nullable()->after('last_update');
            $table->string('number_whatsapp')->nullable()->after('details_last_updated');
        });

        // Ahora preparamos la columna account_id para la clave foránea
        if (Schema::hasColumn('devices', 'account_id')) {
            // Modificamos la lógica para reflejar la relación correcta:
            // En devices, account_id guarda el valor de accountId (string) de la tabla accounts

            // En lugar de crear una clave foránea, que sería incorrecta,
            // nos aseguramos de que los account_id existentes sean válidos
            DB::statement('UPDATE devices SET account_id = NULL WHERE account_id NOT IN (SELECT accountId FROM accounts) AND account_id IS NOT NULL');

            // No añadimos la clave foránea porque las columnas no son compatibles (string vs integer)
            // Para tener una relación correcta, necesitaríamos un campo devices.account_id_fk
            // que apunte a accounts.id, no a accounts.accountId
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Ya no necesitamos eliminar la clave foránea, porque no la estamos creando
            // Solo eliminamos los campos que añadimos
            $table->dropColumn(['details_last_updated', 'number_whatsapp']);
        });
    }
};
