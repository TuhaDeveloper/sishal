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
        // MySQL doesn't support directly modifying ENUM, so we need to use raw SQL
        \DB::statement("ALTER TABLE `bulk_discounts` MODIFY COLUMN `type` ENUM('percentage', 'fixed', 'free_delivery') DEFAULT 'percentage'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        \DB::statement("ALTER TABLE `bulk_discounts` MODIFY COLUMN `type` ENUM('percentage', 'fixed') DEFAULT 'percentage'");
    }
};
