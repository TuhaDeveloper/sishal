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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->enum('from_type', ['branch', 'warehouse', 'external'])->nullable();
            $table->unsignedBigInteger('from_id')->nullable();

            // To (destination or requester)
            $table->enum('to_type', ['branch', 'warehouse','employee'])->default('branch');
            $table->unsignedBigInteger('to_id')->nullable();

            // Product and quantity
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 2);

            // Type of record and status
            $table->enum('type', ['request', 'transfer'])->default('transfer');
            $table->enum('status', ['pending', 'approved', 'rejected', 'shipped', 'delivered'])->default('pending');

            // Actors
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('shipped_by')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();

            // Timestamps for workflow steps
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Optional notes
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
