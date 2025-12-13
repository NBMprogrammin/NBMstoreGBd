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
        Schema::create('zeboune_for_users', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('usernameBss')->constrained('profile_user_bsses')->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->string('username');
            $table->boolean('ConfirmedRelactionUserBss')->default(false);
            $table->string('ConfirmedRelactionUserZeboune')->default();
            $table->string('ConfirmedRelation')->default();
            $table->boolean('CloseRelation')->default(false);
            $table->string('numberPhone')->unique();
            $table->boolean('HaletDeyn')->default(false);
            $table->string('TypeAccounte')->default('create_Use');
            $table->string('TotelDeyn')->default('0');
            $table->string('TotelBayMent')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zeboune_for_users');
    }
};
