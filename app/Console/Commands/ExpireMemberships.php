<?php

namespace App\Console\Commands;

use App\Services\Membership\MembershipExpirationService;
use Illuminate\Console\Command;

class ExpireMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memberships:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired active memberships, mark them as expired, and assign the lowest free tier if available.';

    /**
     * Execute the console command.
     */
    public function handle(MembershipExpirationService $service): int
    {
        $this->info('Checking for expired memberships...');

        $count = $service->processExpirations();

        $this->info("Processed {$count} expired membership(s).");

        return Command::SUCCESS;
    }
}
