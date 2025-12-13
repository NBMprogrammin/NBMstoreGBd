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
        Schema::create('my_orders_pay_bsses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idaccountzeboune')->constrained('zeboune_for_users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('usernameBss')->constrained('profile_user_bsses')->cascadeOnDelete();
            $table->string('namezeboune');
            $table->string('numberzeboune');
            $table->string('namebss');
            $table->string('typeorderforzeboune');
            $table->string('imgUserBss')->nullable()->default(null);
            $table->string('imgprofilezeboune')->nullable()->default(null);
            $table->string('typeaccountzeboune');
            $table->string('currentPay');
            $table->string('totalprodectspay');
            $table->string('totalpriceprodectspay');
            $table->longText('nameprodectspay');
            $table->longText('idprodectspay');
            $table->longText('priceprodectspay');
            $table->longText('quantiteyprodectspay');
            $table->string('allquantitelprodect');
            $table->string('TypeOrder')->nullable()->default(0);
            $table->string('imgconfirmedpay')->nullable('')->default(null);
            $table->integer('totalorders')->nullable()->default(0);
            $table->integer('typepayment')->nullable()->default(0);
            $table->string('paymentmethod')->nullable()->default('');
            $table->string('typMeshole')->nullable()->default(0);
            $table->string('idMeshole')->nullable()->default(0);
            $table->string('numberpaymentmethod')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_orders_pay_bsses');
    }
};
