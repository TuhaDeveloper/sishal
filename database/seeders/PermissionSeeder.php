<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define permissions with categories
        $permissions = [
            // User Management
            ['name' => 'view users', 'category' => 'User Management'],
            ['name' => 'create users', 'category' => 'User Management'],
            ['name' => 'edit users', 'category' => 'User Management'],
            ['name' => 'delete users', 'category' => 'User Management'],
            
            // Product Management
            ['name' => 'view products', 'category' => 'Product Management'],
            ['name' => 'create products', 'category' => 'Product Management'],
            ['name' => 'edit products', 'category' => 'Product Management'],
            ['name' => 'delete products', 'category' => 'Product Management'],
            
            // Order Management
            ['name' => 'view orders', 'category' => 'Order Management'],
            ['name' => 'create orders', 'category' => 'Order Management'],
            ['name' => 'edit orders', 'category' => 'Order Management'],
            ['name' => 'delete orders', 'category' => 'Order Management'],
            
            // Customer Management
            ['name' => 'view customers', 'category' => 'Customer Management'],
            ['name' => 'create customers', 'category' => 'Customer Management'],
            ['name' => 'edit customers', 'category' => 'Customer Management'],
            ['name' => 'delete customers', 'category' => 'Customer Management'],
            
            // Supplier Management
            ['name' => 'view suppliers', 'category' => 'Supplier Management'],
            ['name' => 'create suppliers', 'category' => 'Supplier Management'],
            ['name' => 'edit suppliers', 'category' => 'Supplier Management'],
            ['name' => 'delete suppliers', 'category' => 'Supplier Management'],
            
            // Purchase Management
            ['name' => 'view purchases', 'category' => 'Purchase Management'],
            ['name' => 'create purchases', 'category' => 'Purchase Management'],
            ['name' => 'edit purchases', 'category' => 'Purchase Management'],
            ['name' => 'delete purchases', 'category' => 'Purchase Management'],
            
            // Sales Management
            ['name' => 'view sales', 'category' => 'Sales Management'],
            ['name' => 'create sales', 'category' => 'Sales Management'],
            ['name' => 'edit sales', 'category' => 'Sales Management'],
            ['name' => 'delete sales', 'category' => 'Sales Management'],
            
            // Inventory Management
            ['name' => 'view inventory', 'category' => 'Inventory Management'],
            ['name' => 'manage inventory', 'category' => 'Inventory Management'],
            ['name' => 'view stock transfers', 'category' => 'Inventory Management'],
            ['name' => 'create stock transfers', 'category' => 'Inventory Management'],
            
            // Financial Management
            ['name' => 'view financial accounts', 'category' => 'Financial Management'],
            ['name' => 'create financial accounts', 'category' => 'Financial Management'],
            ['name' => 'edit financial accounts', 'category' => 'Financial Management'],
            ['name' => 'delete financial accounts', 'category' => 'Financial Management'],
            
            // Settings
            ['name' => 'view settings', 'category' => 'Settings'],
            ['name' => 'edit settings', 'category' => 'Settings'],
            
            // Reports
            ['name' => 'view reports', 'category' => 'Reports'],
            ['name' => 'generate reports', 'category' => 'Reports'],
            
            // Banner Management
            ['name' => 'view banners', 'category' => 'Banner Management'],
            ['name' => 'create banners', 'category' => 'Banner Management'],
            ['name' => 'edit banners', 'category' => 'Banner Management'],
            ['name' => 'delete banners', 'category' => 'Banner Management'],
            
            // Branch Management
            ['name' => 'manage global branches', 'category' => 'Branch Management'],
            ['name' => 'create branch', 'category' => 'Branch Management'],
            ['name' => 'view branch details', 'category' => 'Branch Management'],
            ['name' => 'edit branch', 'category' => 'Branch Management'],
            ['name' => 'delete branch', 'category' => 'Branch Management'],
            
            // POS Management
            ['name' => 'make sale', 'category' => 'POS Management'],
            ['name' => 'view sales', 'category' => 'POS Management'],
            ['name' => 'edit sales', 'category' => 'POS Management'],
            ['name' => 'delete sales', 'category' => 'POS Management'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web'
                ],
                [
                    'category' => $permission['category']
                ]
            );
        }

        echo "Permissions created successfully!\n";
    }
} 