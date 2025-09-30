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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('operated_by')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('send_date')->nullable();
            $table->decimal('subtotal',10,2)->nullable();
            $table->decimal('tax')->default('0.00')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_apply', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2);
        
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('note')->nullable();

            $table->text('footer_text')->nullable();

            $table->tinyInteger('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
