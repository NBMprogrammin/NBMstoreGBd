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
        Schema::create('message_eghars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('idbss')->constrained('profile_user_bsses')->cascadeOnDelete()->nullable();
            $table->string('image')->nullable();
            $table->string('sheckMessage')->nullable()->default(null);
            $table->string('typAction')->nullable()->default(0);
            $table->string('ConfirmedRelactionUserZeboune')->nullable()->default(0);
            $table->string('ConfirmedRelation')->nullable()->default(0);
            $table->string('titel');
            $table->string('message');
            $table->string('NameUserSendMessage');
            $table->string('TypeAccountSendMessage');
            $table->boolean('TypeRelationMessageUser')->default(false);
            $table->boolean('TypeRelationMessageUserSend')->default(false);
            $table->boolean('CloceMessage')->default(false);
            $table->string('TypeMessage')->default('Waite');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_eghars');
    }
};
