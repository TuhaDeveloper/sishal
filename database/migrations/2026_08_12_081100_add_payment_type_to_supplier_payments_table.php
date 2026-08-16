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
        if (!Schema::hasColumn('supplier_payments', 'payment_type')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                $table->string('payment_type')->default('bill_wise')->after('purchase_bill_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('supplier_payments', 'payment_type')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                $table->dropColumn('payment_type');
            });
        }
    }
};
