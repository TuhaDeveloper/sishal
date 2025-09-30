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
        Schema::table('product_service_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_service_categories', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }
            if (!Schema::hasColumn('product_service_categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('product_service_categories')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_service_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_service_categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('product_service_categories', 'slug')) {
                $table->dropUnique('product_service_categories_slug_unique');
                $table->dropColumn('slug');
            }
        });
    }
};


