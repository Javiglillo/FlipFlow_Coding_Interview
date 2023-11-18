<?php

namespace App\Console\Commands;

use App\Services\ScraperProductsService;
use Illuminate\Console\Command;

class SaveProductListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save-product-list {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store products from the url in the database';

    /**
     * Execute the console command.
     */
    public function handle(ScraperProductsService $scraper)
    {
        $url = $this->option('url');
        
        if($url){
            $scraper->getProducts($url, 'save');
        }
        else{
            $this->error('You must provide the --url param');
        }
    }
}
