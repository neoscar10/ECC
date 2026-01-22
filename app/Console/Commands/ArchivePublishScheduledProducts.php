<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArchivePublishScheduledProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish archive products whose go_live_at has passed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        
        // Find products that are NOT active, have a go_live_at set, and go_live_at is in the past
        $products = \App\Models\Archive\ArchiveProduct::where('is_active', false)
            ->whereNotNull('go_live_at')
            ->where('go_live_at', '<=', $now)
            ->get();

        $count = $products->count();

        if ($count > 0) {
            foreach ($products as $product) {
                $product->update(['is_active' => true]);
            }
            $this->info("Published {$count} scheduled archive products.");
            \Illuminate\Support\Facades\Log::info("ArchivePublishScheduledProducts: Published {$count} products.");
        } else {
            $this->info('No scheduled products to publish.');
        }
    }
}
