<?php

namespace Kyslik\TimestampSigner;

use Illuminate\Support\ServiceProvider;

/**
 * Class TimestampSignerServiceProvider
 * @package App\Providers
 */
class TimestampSignerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/timestamp-signer.php' => $this->app->configPath().'/'.'timestamp-signer.php',
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/timestamp-signer.php',
            'timestamp-signer'
        );


        $this->app->bind('Kyslik\TimestampSigner\Signer', function() {
            $config = $this->app->config['timestamp-signer'];

            return new Signer($config['secret'], $config['separator'], $config['salt'], $config['default_max_age']);
        });
    }
}
