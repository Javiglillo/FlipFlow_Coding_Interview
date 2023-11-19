<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class ScraperProductsService {

    public function __construct(private ProductService $productService){}
    
    public function getProducts($url, $action): bool
    {
        try{
            $browser = new HttpBrowser(HttpClient::create());
            $browser->request('GET', $url);
            $response = $browser->getResponse();

            if($response->getStatusCode() === 200){
                $body = $response->getContent();
    
                $crawler = new Crawler($body);
                $basePath = $crawler->filter('base')->attr('href');
                $productNodes = $crawler->filter('ul.product-card-list__list > li')->slice(0,5);
                $products = [];

                $productNodes->each(function (Crawler $productNode) use ($basePath, &$products){
                    $product = new Product();
                    $product->name = $productNode->filter('.product-card__title-link')->text();
                    $product->price = $productNode->filter('.product-card__parent')->attr('app_price');
                    $product->image_url = $productNode->filter('.product-card__media-link > img')->attr('src');
                    $product->url = $basePath . $productNode->filter('.product-card__media > a')->attr('href');
                    $products[] = $product->toArray();
                });
                
                if(count($products) > 0){
                    if($action === 'save'){
                        $this->productService->saveProducts($products);
                        return true;
                    } elseif ($action === 'show'){
                        $this->productService->showProducts($products);
                        return true;
                    }
                }
            }
            return false;
        } catch (Exception $e){
            echo 'Caught exception: ' . $e->getMessage() . PHP_EOL;
            return false;
        }

    }
}