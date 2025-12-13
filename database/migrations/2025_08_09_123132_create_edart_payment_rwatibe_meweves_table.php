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
        Schema::create('edart_payment_rwatibe_meweves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idbss')->constrained('profile_user_bsses')->cascadeOnDelete()->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('Ratibe');
            $table->integer('curent');
            $table->integer('MentheNow');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edart_payment_rwatibe_meweves');
    }
};
