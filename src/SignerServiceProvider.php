<?php

namespace Kyslik\Django\Signing;

use Illuminate\Support\ServiceProvider;

class SignerServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/django-signer.php' => $this->app->configPath('django-signer.php'),
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

        $this->app->bind(Signer::class, function ($app) {
            $config = $app->config['django-signer'];

            return new Signer($config['secret'], $config['separator'], $config['salt'], $config['default_max_age']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Signer::class];
    }
}
