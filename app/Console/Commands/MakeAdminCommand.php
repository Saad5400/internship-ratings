<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Signature('app:make-admin {email} {--revoke} {--name= : Name for a newly created user} {--setup-link : Print a fresh password-setup link}')]
#[Description('Grant or revoke admin panel access by email; creates the user (with a signed setup link) when the email is new.')]
class MakeAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $revoke = (bool) $this->option('revoke');

        $user = User::where('email', $email)->first();

        if ($user === null && $revoke) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $created = false;

        if ($user === null) {
            /*
             * Bootstrap path: there is no public registration, so the first
             * admin has to come from somewhere. Create the account with an
             * unguessable random password and hand back a signed setup link
             * where they choose their own.
             */
            $user = User::create([
                'name' => $this->option('name') ?: Str::headline(Str::before($email, '@')),
                'email' => $email,
                'password' => Str::password(40),
                'is_admin' => true,
            ]);
            $created = true;

            $this->info("User [{$email}] created and granted admin access.");
        } else {
            $user->is_admin = ! $revoke;
            $user->save();

            $this->info($revoke
                ? "Admin access revoked for [{$email}]."
                : "Admin access granted to [{$email}].");
        }

        if ($created || $this->option('setup-link')) {
            // Relative signature (route uses signed:relative) so the link
            // works on any host/port the app is reached through.
            $link = url(URL::temporarySignedRoute('admin.setup', now()->addDays(7), ['user' => $user->id], absolute: false));

            $this->newLine();
            $this->line('Password setup link (valid for 7 days):');
            $this->line($link);
        }

        return self::SUCCESS;
    }
}
