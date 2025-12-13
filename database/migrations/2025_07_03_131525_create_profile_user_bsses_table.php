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
        Schema::create('profile_user_bsses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('usernameBss')->unique();
            $table->string('megaleBss');
            $table->string('Numberphone');
            $table->string('gbsbss');
            $table->string('cantryBss');
            $table->string('image')->nullable();
            $table->string('address')->nullable();
            $table->string('Country')->nullable();
            $table->string('discription')->nullable();
            $table->string('CountEdartManye')->nullable();
            $table->string('bigImg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_user_bsses');
    }
};
