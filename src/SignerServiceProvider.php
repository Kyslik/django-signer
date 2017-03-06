<?php

namespace Kyslik\Django\Signing;

use Illuminate\Support\ServiceProvider;


class SignerServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/django-signer.php' => $this->app->configPath().'/'.'django-signer.php',
        ], 'config');
    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/django-signer.php', 'django-signer');

        $this->app->singleton('Kyslik\Django\Signing\Signer', function () {
            $config = $this->app->config['django-signer'];

            return new Signer($config['secret'], $config['separator'], $config['salt'], $config['default_max_age']);
        });
    }
}
