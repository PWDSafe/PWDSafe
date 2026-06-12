<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pwdsafe:make-admin {email}')]
#[Description('Grants a user access to the admin panel')]
class MakeUserAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("{$email} is now an admin.");

        return self::SUCCESS;
    }
}
