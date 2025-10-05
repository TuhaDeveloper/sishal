<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class AssignBannerPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banner:assign-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign banner management permissions to admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all admin users
        $adminUsers = User::where('is_admin', 1)->get();
        
        if ($adminUsers->isEmpty()) {
            $this->error('No admin users found!');
            return;
        }

        // Get banner permissions
        $bannerPermissions = Permission::where('name', 'like', '%banner%')->get();
        
        if ($bannerPermissions->isEmpty()) {
            $this->error('No banner permissions found! Please run the permission seeder first.');
            return;
        }

        // Assign permissions to all admin users
        foreach ($adminUsers as $user) {
            $user->givePermissionTo($bannerPermissions);
            $this->info("Assigned banner permissions to user: {$user->email}");
        }

        $this->info('Banner permissions assigned successfully!');
    }
}
