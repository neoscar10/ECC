<?php

namespace App\Console\Commands;

use App\Services\Shipping\Shiprocket\ShiprocketAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestShiprocketAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shiprocket:test-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Shiprocket authentication and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(ShiprocketAuthService $authService)
    {
        $this->info('Shiprocket Auth Test');

        $email = config('shiprocket.email');
        $password = config('shiprocket.password');

        if (empty($email) || empty($password)) {
            $this->error('Credentials: NOT SET');
            return 1;
        }

        $this->line('Credentials: SET');

        try {
            $this->line('Attempting to fetch token...');
            $token = $authService->refreshToken();
            
            $this->line('Token: ' . Str::mask($token, '*', 5, strlen($token) - 10));
            $this->info('Status: OK');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Status: FAILED');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
