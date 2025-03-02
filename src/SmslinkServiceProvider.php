<?php

namespace LHDev\Smslink;

use Illuminate\Support\ServiceProvider;

class SmslinkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/smslink.php' => config_path('smslink.php')
        ], 'smslink_config');
    }

    public function register(): void
    {
        $this->app->bind(Smslink::class, function() {
            $config_data = [
                'connection_id' => config('smslink.connection_id'),
                'connection_password' => config('smslink.connection_password'),
            ];

            return new Smslink($config_data);
        });
    }
}