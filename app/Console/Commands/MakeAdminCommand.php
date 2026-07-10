<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {email} {--revoke}')]
#[Description('Grant or revoke admin panel access for a user by email.')]
class MakeAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $revoke = $this->option('revoke');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $user->is_admin = ! $revoke;
        $user->save();

        if ($revoke) {
            $this->info("Admin access revoked for [{$email}].");
        } else {
            $this->info("Admin access granted to [{$email}].");
        }

        return self::SUCCESS;
    }
}
