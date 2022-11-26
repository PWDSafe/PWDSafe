<?php

namespace App\Providers;

use App\Helpers\Encryption;
use App\Helpers\LdapAuthentication;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton(Encryption::class, function () {
            return new Encryption();
        });

        app()->singleton(LdapAuthentication::class, function () {
            return new LdapAuthentication();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
