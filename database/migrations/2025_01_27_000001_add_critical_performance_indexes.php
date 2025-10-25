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
        // Add critical indexes for product performance
        try {
            Schema::table('products', function (Blueprint $table) {
                // Index for product details page (slug lookup)
                if (!$this->indexExists('products', 'products_slug_idx')) {
                    $table->index('slug', 'products_slug_idx');
                }
                
                // Index for category filtering
                if (!$this->indexExists('products', 'products_category_id_idx')) {
                    $table->index('category_id', 'products_category_id_idx');
                }
                
                // Index for price sorting and filtering
                if (!$this->indexExists('products', 'products_price_idx')) {
                    $table->index('price', 'products_price_idx');
                }
                
                // Index for discount filtering
                if (!$this->indexExists('products', 'products_discount_idx')) {
                    $table->index('discount', 'products_discount_idx');
                }
                
                // Composite index for common product queries
                if (!$this->indexExists('products', 'products_type_status_price_idx')) {
                    $table->index(['type', 'status', 'price'], 'products_type_status_price_idx');
                }
                
                // Index for SKU lookups
                if (!$this->indexExists('products', 'products_sku_idx')) {
                    $table->index('sku', 'products_sku_idx');
                }
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        // Add indexes for product variations
        try {
            Schema::table('product_variations', function (Blueprint $table) {
                if (!$this->indexExists('product_variations', 'product_variations_product_status_idx')) {
                    $table->index(['product_id', 'status'], 'product_variations_product_status_idx');
                }
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        // Add indexes for product galleries
        try {
            Schema::table('product_galleries', function (Blueprint $table) {
                if (!$this->indexExists('product_galleries', 'product_galleries_product_id_idx')) {
                    $table->index('product_id', 'product_galleries_product_id_idx');
                }
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        // Add indexes for product attributes
        try {
            Schema::table('product_attributes', function (Blueprint $table) {
                if (!$this->indexExists('product_attributes', 'product_attributes_product_id_idx')) {
                    $table->index('product_id', 'product_attributes_product_id_idx');
                }
                if (!$this->indexExists('product_attributes', 'product_attributes_attribute_id_idx')) {
                    $table->index('attribute_id', 'product_attributes_attribute_id_idx');
                }
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName)
    {
        try {
            $indexes = \DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_slug_idx');
            $table->dropIndex('products_category_id_idx');
            $table->dropIndex('products_price_idx');
            $table->dropIndex('products_discount_idx');
            $table->dropIndex('products_type_status_price_idx');
            $table->dropIndex('products_sku_idx');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex('product_variations_product_status_idx');
        });

        Schema::table('product_galleries', function (Blueprint $table) {
            $table->dropIndex('product_galleries_product_id_idx');
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropIndex('product_attributes_product_id_idx');
            $table->dropIndex('product_attributes_attribute_id_idx');
        });
    }
};
