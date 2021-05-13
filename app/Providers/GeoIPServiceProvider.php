<?php

namespace App\Providers;

use Illuminate\Support\Str;
use App\Custom\GeoIP\GeoIP;
use Torann\GeoIP\Console\Clear;
use Torann\GeoIP\Console\Update;
use Illuminate\Support\ServiceProvider;

class GeoIPServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGeoIpService();

        if ($this->app->runningInConsole()) {
            $this->registerGeoIpCommands();
        }
    }

    /**
     * Register currency provider.
     *
     * @return void
     */
    public function registerGeoIpService()
    {
        $this->app->singleton('geoip', function ($app) {
            return new GeoIP(
                $app->config->get('geoip', []),
                $app['cache']
            );
        });
    }


    /**
     * Register commands.
     *
     * @return void
     */
    public function registerGeoIpCommands()
    {
        $this->commands([
            Update::class,
            Clear::class,
        ]);
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen()
    {
        return Str::contains($this->app->version(), 'Lumen') === true;
    }
}
