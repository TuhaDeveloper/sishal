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
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained('chart_of_accounts')
                ->onDelete('cascade');

            $table->enum('type', ['bank', 'mobile'])->default('bank');

            // Shared fields
            $table->string('provider_name'); // Bank name or Mobile operator (e.g., DBBL, bKash)
            $table->string('account_number');
            $table->string('account_holder_name')->nullable();
            $table->string('currency', 10)->default('BDT');

            // Optional for bank accounts
            $table->string('branch_name')->nullable();
            $table->string('swift_code')->nullable();

            // Optional for mobile banking
            $table->string('mobile_number')->nullable(); // For bKash/Nagad/etc.
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};
