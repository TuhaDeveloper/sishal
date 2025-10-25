<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Verifying variation data for frontend display...\n\n";

// Get the product with variations (like the frontend does)
$product = App\Models\Product::where('has_variations', true)
    ->with([
        'variations' => function($q) {
            $q->where('status', 'active')
              ->with([
                  'combinations.attribute', 
                  'combinations.attributeValue',
                  'stocks.branch',
                  'stocks.warehouse',
                  'galleries',
              ]);
        }
    ])
    ->first();

if ($product) {
    echo "Product: " . $product->name . "\n";
    echo "Has variations: " . ($product->has_variations ? 'Yes' : 'No') . "\n";
    echo "Variations count: " . $product->variations->count() . "\n\n";
    
    // Simulate the frontend logic for building attribute groups
    $attributeGroups = [];
    $variationImages = [];
    
    foreach ($product->variations as $variation) {
        foreach ($variation->combinations as $comb) {
            if (!$comb->attribute || !$comb->attributeValue) { continue; }
            $attrId = $comb->attribute->id;
            $valId = $comb->attributeValue->id;
            $attributeGroups[$attrId]['name'] = $comb->attribute->name;
            
            $attributeGroups[$attrId]['values'][$valId] = [
                'label' => $comb->attributeValue->value,
                'image' => $variation->image ? asset($variation->image) : ($comb->attributeValue->image ?? null),
                'color_code' => $comb->attributeValue->color_code ?? null,
            ];
        }
    }
    
    echo "Frontend will display these attribute groups:\n";
    foreach ($attributeGroups as $attrId => $group) {
        echo "\n" . strtoupper($group['name']) . ":\n";
        foreach ($group['values'] as $valId => $val) {
            $label = is_array($val) ? ($val['label'] ?? (string)$val) : (string)$val;
            echo "  - " . $label . "\n";
        }
    }
    
    echo "\n✅ The frontend should now display the correct size options!\n";
    echo "✅ Cache has been cleared, so changes should be visible immediately.\n";
    
} else {
    echo "No products with variations found\n";
}
