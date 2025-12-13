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
        Schema::create('profile_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('Gender')->nullable();
            $table->string('NumberPhone')->nullable();
            $table->string('city')->nullable();
            $table->string('data_of_birth')->nullable();
            $table->string('bastgaming')->nullable();
            $table->string('bastclabe')->nullable();
            $table->string('address')->nullable();
            $table->string('cantry')->nullable();
            $table->string('image')->nullable();
            $table->string('TypProfileUser')->default('user');
            $table->string('relationForUsers')->default('user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_users');
    }
};
