<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecc:cleanup-orphans {--dry-run : Run the command without deleting records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes memberships and applications that are linked to non-existent or deleted users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('--- DRY RUN MODE: No records will be deleted ---');
        }

        // 1. Memberships
        $this->info('Checking for orphaned memberships...');
        
        $orphanedMemberships = Membership::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('users')
                  ->whereRaw('users.id = memberships.user_id');
        })->get();

        if ($orphanedMemberships->isEmpty()) {
            $this->info('No orphaned memberships found.');
        } else {
            $count = $orphanedMemberships->count();
            $this->warn("Found {$count} orphaned memberships.");
            
            if (!$dryRun) {
                if ($this->confirm("Are you sure you want to delete these {$count} memberships?", true)) {
                    Membership::whereIn('id', $orphanedMemberships->pluck('id'))->delete();
                    $this->success("Deleted {$count} memberships.");
                }
            } else {
                $this->info("Would have deleted {$count} memberships.");
            }
        }

        $this->newLine();

        // 2. Membership Applications
        $this->info('Checking for orphaned membership applications...');
        
        $orphanedApps = MembershipApplication::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('users')
                  ->whereRaw('users.id = membership_applications.user_id');
        })->get();

        if ($orphanedApps->isEmpty()) {
            $this->info('No orphaned applications found.');
        } else {
            $count = $orphanedApps->count();
            $this->warn("Found {$count} orphaned applications.");
            
            if (!$dryRun) {
                if ($this->confirm("Are you sure you want to delete these {$count} applications?", true)) {
                    MembershipApplication::whereIn('id', $orphanedApps->pluck('id'))->delete();
                    $this->success("Deleted {$count} applications.");
                }
            } else {
                $this->info("Would have deleted {$count} applications.");
            }
        }

        $this->newLine();
        $this->info('Cleanup process complete.');
    }

    /**
     * Helper to output success message in green.
     */
    private function success($message)
    {
        $this->line("<fg=green>{$message}</>");
    }
}
