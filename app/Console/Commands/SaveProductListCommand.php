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
                $result = $scraper->getProducts($url, 'save');
                $result === true ? $this->info('Products stored correctly') : $this->error('Products could not be stored');
        }
        else{
            $this->error('You must provide the --url param');
        }
    }
}
