<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\Otp\OtpService;

class TestOtpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:otp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OTP delivery for password reset';

    /**
     * Execute the console command.
     */
    public function handle(OtpService $otpService)
    {
        $phone = '+2349136531688'; // Valid format
        $user = User::create([
            'name' => 'test', 
            'email' => 'test@otp.com', 
            'password' => bcrypt('password'), 
            'phone' => $phone
        ]);

        if ($user) {
            $this->info("Created User ID: {$user->id}, Phone: {$user->phone}");
            try {
                $res = $otpService->requestPasswordResetOtp($user, $user->phone);
                $this->info("Result: " . json_encode($res, JSON_PRETTY_PRINT));
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
            $user->forceDelete();
        } else {
            $this->warn("Failed to create user.");
        }
    }
}
