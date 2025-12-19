<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DebugLogin extends Command
{
    protected $signature = 'debug:login {email=admin@admin.com} {password=admin}';
    protected $description = 'Check if user exists and verify password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $this->info("Checking login for: {$email}");

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return Command::FAILURE;
        }

        $this->info("User found: {$user->name} (ID: {$user->id})");

        if (Hash::check($password, $user->password)) {
            $this->info("Password matches!");
            return Command::SUCCESS;
        } else {
            $this->error("Password does NOT match");
            $this->info("Stored hash: " . $user->password);
            $this->info("Test hash: " . Hash::make($password));
            return Command::FAILURE;
        }
    }
}
