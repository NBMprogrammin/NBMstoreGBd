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
        Schema::create('prodect_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // $table->foreignId('IdBss')->constrained('profile_user_bsses')->cascadeOnDelete();
            $table->longText('IdBss');
            $table->longText('categoryID');
            $table->string('name');
            $table->string('price');
            $table->string('totaleinstorage');
            $table->string('TypePayprd');
            $table->string('currentPay');
            $table->string('descprice')->nullable();
            $table->string('discreption')->nullable();
            $table->string('img')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodect_users');
    }
};
