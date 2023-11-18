<?php

namespace App\Services;

use App\Models\Product;

class ProductService {
    
    public function saveProducts($products){
        Product::insert($products);
    }

    public function showProducts($products) {
        echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}