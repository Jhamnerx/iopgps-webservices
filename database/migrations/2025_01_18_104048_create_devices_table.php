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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('id_api')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('name')->nullable();
            $table->string('plate')->nullable();
            $table->string('imei')->nullable();
            $table->text('services')->nullable();
            $table->text('last_status')->nullable();
            $table->text('last_position')->nullable();
            $table->dateTime('last_update')->nullable();
            $table->integer('latest_position_id')->nullable();
            $table->text('url_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
