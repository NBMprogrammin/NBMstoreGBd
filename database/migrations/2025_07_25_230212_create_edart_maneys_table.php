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
        Schema::create('edart_maneys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('idbss')->constrained('profile_user_bsses')->cascadeOnDelete();
            $table->foreignId('idTweve')->constrained('edaret_mewevins')->cascadeOnDelete()->nullable(null);
            $table->string('NumberMeshol');
            $table->integer('totalEdichal');
            $table->integer('totalEstehlakat');
            $table->integer('TotalErbahe');
            $table->string('currentPay');
            $table->string('descripctionforday')->nullable();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edart_maneys');
    }
};
