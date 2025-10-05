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
        Schema::table('reviews', function (Blueprint $table) {
            // Check if foreign key constraints don't already exist
            if (!Schema::hasColumn('reviews', 'user_id') || !Schema::hasColumn('reviews', 'product_id')) {
                return;
            }
            
            // Add foreign key constraints to prevent orphaned records
            try {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
                \Log::info('Foreign key for user_id might already exist: ' . $e->getMessage());
            }
            
            try {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
                \Log::info('Foreign key for product_id might already exist: ' . $e->getMessage());
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
        });
    }
};
