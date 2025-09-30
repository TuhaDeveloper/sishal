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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['product', 'service'])->default('product');
            $table->string('sku')->unique();
            $table->text('short_desc')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('product_service_categories');
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->default('0');
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
