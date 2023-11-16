<?php

namespace App\Console\Commands;

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
    public function handle()
    {
        //
    }
}
