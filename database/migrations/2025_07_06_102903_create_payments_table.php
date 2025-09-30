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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum('payment_for',['pos','invoice','order','service'])->nullable();
            $table->bigInteger('pos_id')->nullable();
            $table->bigInteger('invoice_id')->nullable();
            $table->date('payment_date');
            $table->decimal('amount',12,2);

            $table->bigInteger('account_id')->nullable();
            $table->string('payment_method')->default('cash');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
