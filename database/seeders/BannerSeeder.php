<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Banner;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample banners
        Banner::create([
            'title' => 'Welcome to Our Store',
            'description' => 'Discover amazing products and great deals!',
            'position' => 'top',
            'status' => 'active',
            'sort_order' => 1,
            'link_url' => '/products',
            'link_text' => 'Shop Now'
        ]);

        Banner::create([
            'title' => 'Special Offer',
            'description' => 'Get 20% off on all items this week!',
            'position' => 'middle',
            'status' => 'active',
            'sort_order' => 2,
            'link_url' => '/sale',
            'link_text' => 'View Sale'
        ]);

        Banner::create([
            'title' => 'Newsletter Signup',
            'description' => 'Stay updated with our latest news and offers.',
            'position' => 'sidebar',
            'status' => 'active',
            'sort_order' => 1,
            'link_url' => '/newsletter',
            'link_text' => 'Subscribe'
        ]);

        echo "Sample banners created successfully!\n";
    }
}
