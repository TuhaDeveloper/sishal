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
        Schema::table('bulk_discounts', function (Blueprint $table) {
            $table->boolean('free_delivery')->default(false)->after('value')->comment('Enable free delivery for selected products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_discounts', function (Blueprint $table) {
            $table->dropColumn('free_delivery');
        });
    }
};
