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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_financial_account_id')->constrained('financial_accounts')->onDelete('cascade');
            $table->foreignId('to_financial_account_id')->constrained('financial_accounts')->onDelete('cascade');

            $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');

            $table->decimal('amount', 15, 2);
            $table->date('transfer_date')->index();
            $table->string('reference')->nullable();
            $table->string('memo')->nullable();

            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
