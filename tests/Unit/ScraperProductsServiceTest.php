<?php

namespace Tests\Unit;

use App\Services\ProductService;
use App\Services\ScraperProductsService;
use Tests\TestCase;
use Mockery;

class ScraperProductsServiceTest extends TestCase
{
    private $url = 'https://www.carrefour.es/supermercado/congelados/cat21449123/c';

    public function testGetProductsSaveAction(): void
    {
        $mockProductService = Mockery::mock(ProductService::class);
        $mockProductService->shouldReceive('saveProducts')->once();

        $this->app->instance(ProductService::class, $mockProductService);
        $scraperProductsService = new ScraperProductsService($mockProductService);
        $result = $scraperProductsService->getProducts($this->url, 'save');
        $this->assertTrue($result);
    }

    public function testGetProductsShowAction(): void
    {
        $mockProductService = Mockery::mock(ProductService::class);
        $mockProductService->shouldReceive('showProducts')->once();

        $this->app->instance(ProductService::class, $mockProductService);
        $scraperProductsService = new ScraperProductsService($mockProductService);
        $result = $scraperProductsService->getProducts($this->url, 'show');
        $this->assertTrue($result);
    }

    public function testExceptionIsThrownForInvalidUrl(): void
    {
        $mockProductService = Mockery::mock(ProductService::class);

        $this->app->instance(ProductService::class, $mockProductService);
        $scraperProductsService = new ScraperProductsService($mockProductService);
        
        $result = $scraperProductsService->getProducts($this->url . 'URLINVALIDA', 'show');
        $this->assertFalse($result);
    }
}