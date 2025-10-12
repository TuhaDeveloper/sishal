<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING PRODUCTS ===\n";

// Check products with shirt-related slugs
$products = \App\Models\Product::where('slug', 'like', '%shirt%')
    ->orWhere('slug', 'like', '%tshirt%')
    ->get(['id', 'name', 'slug']);

echo "Products found:\n";
foreach($products as $product) {
    echo "ID: {$product->id} | Name: {$product->name} | Slug: {$product->slug}\n";
}

echo "\n=== SPECIFIC CHECKS ===\n";

// Check specific slug
$shirtProduct = \App\Models\Product::where('slug', 'shirt')->first();
if($shirtProduct) {
    echo "Product with slug 'shirt': ID {$shirtProduct->id}, Name: {$shirtProduct->name}\n";
} else {
    echo "No product found with slug 'shirt'\n";
}

$tshirtProduct = \App\Models\Product::where('slug', 'tshirt')->first();
if($tshirtProduct) {
    echo "Product with slug 'tshirt': ID {$tshirtProduct->id}, Name: {$tshirtProduct->name}\n";
} else {
    echo "No product found with slug 'tshirt'\n";
}

echo "\n=== ALL PRODUCTS ===\n";
$allProducts = \App\Models\Product::all(['id', 'name', 'slug']);
foreach($allProducts as $product) {
    echo "ID: {$product->id} | Name: {$product->name} | Slug: {$product->slug}\n";
}
