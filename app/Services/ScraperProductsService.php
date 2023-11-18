<?php

namespace App\Services;

use App\Models\Product;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class ScraperProductsService {
    
    public function saveProducts($url){
        $browser = new HttpBrowser(HttpClient::create());
        $browser->request('GET', $url);
        $response = $browser->getResponse();
        $body = $response->getContent();

        $crawler = new Crawler($body);
        $basePath = $crawler->filter('base')->attr('href');
        $productNodes = $crawler->filter('ul.product-card-list__list > li')->slice(0,5);

        $productNodes->each(function (Crawler $productNode) use  ($basePath){
            $product = new Product();
            $product->name = $productNode->filter('.product-card__title-link')->text();
            $product->price = $productNode->filter('.product-card__parent')->attr('app_price');
            $product->image_url = $productNode->filter('.product-card__media-link > img')->attr('src');
            $product->url = $basePath . $productNode->filter('.product-card__media > a')->attr('href');
            $product->save();
        });
    }
}