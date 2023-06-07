<?php

namespace Twinbrotherstudio\Twinbrotherstudiokit;

use Illuminate\Support\ServiceProvider;

class TbsPackageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->publishes([
            __DIR__ . '/../resources/config/tbs-kit.php' => config_path('tbs-kit.php'),
        ]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'demo');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/demo'),
        ]);

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'demo');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/demo'),
        ]);
    }


    public static function postAutoloadDump()
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';

        // static::clearCompiled();
    }
    // protected static function clearCompiled()
    // {
    //     $laravel = new Application(getcwd());

    //     if (is_file($configPath = $laravel->getCachedConfigPath())) {
    //         @unlink($configPath);
    //     }

    //     if (is_file($servicesPath = $laravel->getCachedServicesPath())) {
    //         @unlink($servicesPath);
    //     }

    //     if (is_file($packagesPath = $laravel->getCachedPackagesPath())) {
    //         @unlink($packagesPath);
    //     }
    // }
}
