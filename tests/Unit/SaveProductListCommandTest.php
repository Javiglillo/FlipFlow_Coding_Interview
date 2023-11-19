<?php

namespace Tests\Unit;

use App\Services\ScraperProductsService;
use Tests\TestCase;
use Mockery;

class SaveProductListCommandTest extends TestCase
{
    private $url = 'https://info.flipflow.io/';

    public function testSaveProductListCommandCorrectly(): void
    {
        $mockScraper = Mockery::mock(ScraperProductsService::class);
        $mockScraper->shouldReceive('getProducts')->andReturn(true);
        
        $this->app->instance(ScraperProductsService::class, $mockScraper);

        $this->artisan('save-product-list', ['--url' => $this->url])
            ->expectsOutput('Products stored correctly');

        $mockScraper->shouldHaveReceived('getProducts')->once()->with($this->url, 'save');
    }

    public function testSaveProductListCommandIncorrectly(): void
    {
        $mockScraper = Mockery::mock(ScraperProductsService::class);
        $mockScraper->shouldReceive('getProducts')->andReturn(false);
        
        $this->app->instance(ScraperProductsService::class, $mockScraper);

        $this->artisan('save-product-list', ['--url' => $this->url])
            ->expectsOutput('Products could not be stored');

        $mockScraper->shouldHaveReceived('getProducts')->once()->with($this->url, 'save');
    }

    public function testSaveProductListCommandWithoutUrl(): void
    {
        $mockScraper = Mockery::mock(ScraperProductsService::class);
        
        $this->app->instance(ScraperProductsService::class, $mockScraper);

        $this->artisan('save-product-list')
            ->expectsOutput('You must provide the --url param');

        $mockScraper->shouldNotHaveReceived('getProducts');
    }
}