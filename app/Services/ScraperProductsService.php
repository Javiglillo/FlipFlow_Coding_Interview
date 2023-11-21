<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;

class ScraperProductsService {

    public function __construct(private ProductService $productService){}
    
    public function getDeliveryAddressDetails(): object
    {
        $deliveryAddress = 'Drive Peatón MK Quevedo';
        $browser = new HttpBrowser(HttpClient::create());
        $browser->request('GET', 'https://www.carrefour.es/cloud-api/salepoints/v1/drives');

        $response = $browser->getResponse();
        $content = $response->getContent();
        $contentJSON = json_decode($content, false);

        $salePoints = array_map(function($group) {
            return $group->sale_points;
        }, $contentJSON->groups);

        $salePoints = array_merge(...$salePoints);

        $driveQuevedo = array_filter($salePoints, function($salePoint) use ($deliveryAddress) {
            return $salePoint->name === $deliveryAddress;
        });

        return array_values($driveQuevedo)[0];
    }

    public function getProducts($url, $action): bool
    {
        try{
            $deliveryAddressDetails = $this->getDeliveryAddressDetails();
            $browser = new HttpBrowser(HttpClient::create());
            $browser->request('GET', $url);

            $newDriveCookie = new Cookie(
                'salepoint',
                $deliveryAddressDetails->sale_point_id . '|' . $deliveryAddressDetails->store_id . '||DRIVE|1',
                time() + 3600,
                '/',
                'carrefour.es'
            );

            $browser->getCookieJar()->set($newDriveCookie);
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