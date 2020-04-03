<?php
namespace App\Providers;

use App\Custom\RongCloud\RongCloud;
use Latrell\RongCloud\RongCloudServiceProvider as ServiceProvider;

class RongCloudServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		parent::boot();
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
        parent::register();

		$this->app->singleton('rcloud', function ($app) {
			$config = $app->config->get('latrell-rcloud');
			return new RongCloud($config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'rcloud'
		];
	}
}
