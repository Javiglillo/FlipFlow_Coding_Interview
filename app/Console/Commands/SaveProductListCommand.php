<?php

namespace App\Console\Commands;

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
    public function handle()
    {
        //
    }
}
