<?php

namespace Tests\Unit;

use App\Services\ScraperProductsService;
use Tests\TestCase;
use Mockery;

class ShowProductListCommandTest extends TestCase
{
    private $url = 'https://info.flipflow.io/';

    public function testShowProductListCommandCorrectly(): void
    {
        $mockScraper = Mockery::mock(ScraperProductsService::class);
        $mockScraper->shouldReceive('getProducts')->andReturn(true);
        
        $this->app->instance(ScraperProductsService::class, $mockScraper);

        $this->artisan('show-product-list', ['--url' => $this->url])
            ->expectsOutput('Products showed correctly');

        $mockScraper->shouldHaveReceived('getProducts')->once()->with($this->url, 'show');
    }

    public function testShowProductListCommandIncorrectly(): void
    {
        $mockScraper = Mockery::mock(ScraperProductsService::class);
        $mockScraper->shouldReceive('getProducts')->andReturn(false);
        
        $this->app->instance(ScraperProductsService::class, $mockScraper);

        $this->artisan('show-product-list', ['--url' => $this->url])
            ->expectsOutput('Products could not be showed');

        $mockScraper->shouldHaveReceived('getProducts')->once()->with($this->url, 'show');
    }

    public function testShowProductListCommandWithoutUrl(): void
    {
        $mockScraper = Mockery::mock(ScraperProductsService::class);
        
        $this->app->instance(ScraperProductsService::class, $mockScraper);

        $this->artisan('show-product-list')
            ->expectsOutput('You must provide the --url param');

        $mockScraper->shouldNotHaveReceived('getProducts');
    }
}