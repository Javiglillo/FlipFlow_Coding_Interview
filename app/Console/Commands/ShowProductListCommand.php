<?php

namespace App\Console\Commands;

use App\Services\ScraperProductsService;
use Illuminate\Console\Command;

class ShowProductListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'show-product-list {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show products from the url in json format';

    /**
     * Execute the console command.
     */
    public function handle(ScraperProductsService $scraper)
    {
        $url = $this->option('url');
        
        if($url){
            $scraper->getProducts($url, 'show');
        }
        else{
            $this->error('You must provide the --url param');
        }
    }
}
