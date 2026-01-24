<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Auctions\AuctionLifecycleService;

class AuctionsCheckLifecycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:check-lifecycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and transition auction lot statuses (start upcoming, end live)';

    /**
     * Execute the console command.
     */
    public function handle(AuctionLifecycleService $service)
    {
        // Simple delegator to service to keep logic centralized
        $service->checkLifecycle();
        
        $this->info('Auction lifecycle check completed.');
    }
}
