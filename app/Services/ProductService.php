<?php

namespace App\Services;

use App\Models\Product;

class ProductService {
    
    public function saveProducts($products): void
    {
        Product::insert($products);
    }

    public function showProducts($products): void
    {
        echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}