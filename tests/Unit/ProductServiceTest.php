<?php

namespace Tests\Unit;

use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private array $products;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = new ProductService();
        $this->products = [
            [
                'name' => 'Product 1', 
                'price' => 10.99, 
                'image_url' => 'https://static.carrefour.es/hd_350x_/img_pim_food/651364_00_1.jpg', 
                'url' => 'https://www.carrefour.es/supermercado/congelados/cat21449123/c'
            ],
            [
                'name' => 'Product 2', 
                'price' => 8.69, 
                'image_url' => 'https://static.carrefour.es/hd_350x_/img_pim_food/651364_00_1.jpg', 
                'url' => 'https://www.carrefour.es/supermercado/congelados/cat21449123/c'
            ],
            [
                'name' => 'Product 3', 
                'price' => 4.60, 
                'image_url' => 'https://static.carrefour.es/hd_350x_/img_pim_food/651364_00_1.jpg', 
                'url' => 'https://www.carrefour.es/supermercado/congelados/cat21449123/c'
            ]
        ];
    }

    public function testSaveProducts(): void
    {
        $this->productService->saveProducts($this->products);
        $this->assertDatabaseCount('products', count($this->products));

        foreach($this->products as $product){
            $this->assertDatabaseHas('products', $product);
        }
    }

    public function testShowProducts(): void
    {
        ob_start();
        $this->productService->showProducts($this->products);
        $output = ob_get_clean();
        $expectedJson = json_encode($this->products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        $this->assertEquals($expectedJson, $output);
    }
}