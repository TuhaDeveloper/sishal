<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Banner;

class CreateSampleBanners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banner:create-samples';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample banners for testing carousel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating sample banners for carousel...');
        
        // Clear existing banners
        Banner::truncate();
        
        // Create sample banners
        $banners = [
            [
                'title' => 'Welcome to Our Store',
                'description' => 'Discover amazing products and great deals!',
                'position' => 'top',
                'status' => 'active',
                'sort_order' => 1,
                'link_url' => '/products',
                'link_text' => 'Shop Now'
            ],
            [
                'title' => 'Special Offer - 50% Off',
                'description' => 'Get 50% off on all items this week! Limited time offer.',
                'position' => 'top',
                'status' => 'active',
                'sort_order' => 2,
                'link_url' => '/sale',
                'link_text' => 'View Sale'
            ],
            [
                'title' => 'New Collection',
                'description' => 'Check out our latest collection of premium products.',
                'position' => 'top',
                'status' => 'active',
                'sort_order' => 3,
                'link_url' => '/new-arrivals',
                'link_text' => 'Explore'
            ],
            [
                'title' => 'Free Shipping',
                'description' => 'Free shipping on orders over $50. No minimum purchase required.',
                'position' => 'top',
                'status' => 'active',
                'sort_order' => 4,
                'link_url' => '/shipping-info',
                'link_text' => 'Learn More'
            ]
        ];
        
        foreach ($banners as $bannerData) {
            Banner::create($bannerData);
            $this->line("Created banner: {$bannerData['title']}");
        }
        
        $this->info('Sample banners created successfully!');
        $this->line('You can now see the carousel in action on the home page.');
    }
}
