<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all product-related cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing product cache...');
        
        // Clear product listing cache
        $this->clearCachePattern('products_list_*');
        
        // Clear product details cache
        $this->clearCachePattern('product_details_*');
        
        // Clear API cache
        $this->clearCachePattern('top_selling_products_*');
        $this->clearCachePattern('new_arrivals_products_*');
        $this->clearCachePattern('best_deals_products_*');
        
        $this->info('Product cache cleared successfully!');
    }
    
    /**
     * Clear cache by pattern
     */
    private function clearCachePattern($pattern)
    {
        try {
            $store = Cache::getStore();
            
            // Check if we're using Redis cache driver
            if (method_exists($store, 'getRedis')) {
                // Redis-specific pattern clearing
                $keys = $store->getRedis()->keys($pattern);
                if (!empty($keys)) {
                    $store->getRedis()->del($keys);
                    $this->line("Cleared " . count($keys) . " cache entries matching: {$pattern}");
                }
            } else {
                // For non-Redis drivers (database, file, array), we need to clear cache differently
                // Since pattern matching isn't available, we'll clear the entire cache
                $this->info("Clearing entire cache due to pattern matching not supported for current driver: " . get_class($store));
                Cache::flush();
                $this->line("Cleared entire cache (pattern matching not supported for current driver)");
            }
        } catch (\Exception $e) {
            // Fallback: try to clear individual cache entries or flush entire cache
            $this->warn("Could not clear cache pattern {$pattern}: " . $e->getMessage());
            try {
                Cache::flush();
                $this->line("Fallback: Cleared entire cache");
            } catch (\Exception $flushException) {
                $this->error("Failed to flush cache: " . $flushException->getMessage());
            }
        }
    }
}
