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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->bigInteger('employee_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');

            $table->decimal('subtotal',12,2);
            $table->decimal('vat',12,2)->nullable();
            $table->decimal('discount',12,2)->nullable();
            $table->decimal('delivery',12,2)->nullable();
            $table->decimal('total',12,2);

            $table->date('estimated_delivery_date')->nullable();
            $table->time('estimated_delivery_time')->nullable();
            $table->enum('status',['pending','approved','shipping','delivered','cancelled'])->default('pending');
            $table->enum('payment_method',['cash','bank-transfer','online-payment'])->default('cash');
            $table->bigInteger('invoice_id')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('created_by');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
