<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('sold_by');
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->date('sale_date');

            $table->decimal('sub_total', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('delivery', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2); // sub_total - discount
            $table->date('estimated_delivery_date')->nullable();
            $table->time('estimated_delivery_time')->nullable();

            $table->enum('status',['pending', 'approved','shipping','delivered','cancelled'])->default('pending');

            $table->unsignedBigInteger('invoice_id')->nullable(); // auto-generated
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos');
    }
};
