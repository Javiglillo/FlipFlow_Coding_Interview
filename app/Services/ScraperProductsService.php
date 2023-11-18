<?php

namespace App\Services;

use App\Models\Product;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class ScraperProductsService {
    
    public function getProducts($url){
        $browser = new HttpBrowser(HttpClient::create());
        $browser->request('GET', $url);
        $response = $browser->getResponse();
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

        return $products;
    }

    public function saveProducts($url){
        $products = $this->getProducts($url);
        Product::insert($products);
    }

    public function showProducts($url) {
        $products = $this->getProducts($url);
        echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}