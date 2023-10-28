<?php

namespace App\Console\Commands;

use App\SharedCredential;
use Illuminate\Console\Command;

class DeleteExpiredSharedCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwdsafe:delete-expired-shared-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up expired shared credentials';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        SharedCredential::deleteExpired();
    }
}
