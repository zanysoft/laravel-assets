<?php
namespace ZanySoft\LaravelAssets;

use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
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
        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('laravel-assets.php')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('assets', function ($app) {
            return new Assets($this->config());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['assets'];
    }

    /**
     * Get the base settings from config file
     *
     * @return array
     */
    public function config()
    {
        $config = config('laravel-assets');

        $config['environment'] = app()->environment();
        $config['public_path'] = public_path();
        $config['asset'] = asset('');

        return $config;
    }
}
