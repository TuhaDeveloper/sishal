<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Review;
use App\Models\User;
use App\Models\Product;

class CleanOrphanedReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:clean-orphaned {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned review records where user or product no longer exists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no records will be deleted');
        }

        // Find reviews with non-existent users
        $orphanedUserReviews = Review::whereNotIn('user_id', User::pluck('id'))->get();
        
        // Find reviews with non-existent products
        $orphanedProductReviews = Review::whereNotIn('product_id', Product::pluck('id'))->get();
        
        $totalOrphaned = $orphanedUserReviews->count() + $orphanedProductReviews->count();
        
        if ($totalOrphaned === 0) {
            $this->info('No orphaned review records found.');
            return;
        }

        $this->info("Found {$totalOrphaned} orphaned review records:");
        $this->info("- Reviews with deleted users: {$orphanedUserReviews->count()}");
        $this->info("- Reviews with deleted products: {$orphanedProductReviews->count()}");

        if ($isDryRun) {
            $this->table(
                ['Review ID', 'User ID', 'Product ID', 'Issue'],
                $orphanedUserReviews->map(function($review) {
                    return [$review->id, $review->user_id, $review->product_id, 'User deleted'];
                })->concat($orphanedProductReviews->map(function($review) {
                    return [$review->id, $review->user_id, $review->product_id, 'Product deleted'];
                }))
            );
            return;
        }

        if ($this->confirm("Do you want to delete these {$totalOrphaned} orphaned review records?")) {
            $deletedCount = 0;
            
            // Delete reviews with non-existent users
            foreach ($orphanedUserReviews as $review) {
                $review->delete();
                $deletedCount++;
            }
            
            // Delete reviews with non-existent products
            foreach ($orphanedProductReviews as $review) {
                $review->delete();
                $deletedCount++;
            }
            
            $this->info("Successfully deleted {$deletedCount} orphaned review records.");
        } else {
            $this->info('Operation cancelled.');
        }
    }
}
