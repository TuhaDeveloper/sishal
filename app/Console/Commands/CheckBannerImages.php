<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Banner;

class CheckBannerImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banner:check-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check banner images and their URLs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking Banner Images...');
        
        $banners = Banner::all();
        
        if ($banners->isEmpty()) {
            $this->warn('No banners found in database.');
            return;
        }
        
        foreach ($banners as $banner) {
            $this->line("Banner ID: {$banner->id}");
            $this->line("Title: {$banner->title}");
            $this->line("Image Path: " . ($banner->image ?: 'No image'));
            
            if ($banner->image) {
                $fullPath = storage_path('app/public/' . $banner->image);
                $exists = file_exists($fullPath);
                $this->line("File exists: " . ($exists ? 'Yes' : 'No'));
                $this->line("Full path: {$fullPath}");
                
                $imageUrl = $banner->image_url;
                $this->line("Image URL: {$imageUrl}");
                
                // Test if the URL is accessible
                $publicPath = public_path('storage/' . $banner->image);
                $publicExists = file_exists($publicPath);
                $this->line("Public file exists: " . ($publicExists ? 'Yes' : 'No'));
                $this->line("Public path: {$publicPath}");
            }
            
            $this->line('---');
        }
    }
}
