<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RemediateDeletedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecc:remediate-deleted-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymizes email and phone fields of already soft-deleted users so those values can be reused.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding soft-deleted users with original emails...');

        $users = User::onlyTrashed()->where('email', 'not like', 'del_%')->get();

        if ($users->isEmpty()) {
            $this->info('No users found requiring remediation.');
            return;
        }

        $count = $users->count();
        $this->info("Found {$count} user(s). Remediating...");

        $bar = $this->output->createProgressBar($count);

        foreach ($users as $user) {
            $prefix = 'del_' . time() . '_' . $user->id . '_';
            
            $maxEmailLength = 255 - strlen($prefix);
            $newEmail = $prefix . substr($user->email, 0, $maxEmailLength);
            
            $updates = [
                'email' => $newEmail
            ];

            if ($user->phone && !str_starts_with($user->phone, 'del_')) {
                $maxPhoneLength = 255 - strlen($prefix);
                $updates['phone'] = $prefix . substr($user->phone, 0, $maxPhoneLength);
            }

            // Using raw update to bypass any other observer/events
            User::withTrashed()->where('id', $user->id)->update($updates);
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Remediation complete.');
    }
}
