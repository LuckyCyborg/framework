<?php

namespace App\Providers;

use Nova\Filesystem\FileNotFoundException;
use Nova\Foundation\Support\Providers\AppServiceProvider as ServiceProvider;


class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the Application Events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the Application's Service Provider.
     *
     * This service provider is a convenient place to register your modules
     * services in the IoC container. If you wish, you may make additional
     * methods or service providers to keep the code more focused and granular.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPackages();

        //
    }

    /**
     * Register the Application's Packages.
     *
     * @return void
     */
    protected function registerPackages()
    {
        $packages = $this->getInstalledPackages();

        foreach ($packages as $package) {
            $namespace = str_replace('/', '\\', $package);

            $provider = $namespace .'\\Providers\\PackageServiceProvider';

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    protected function getInstalledPackages()
    {
        $path = BASEPATH .'vendor' .DS .'nova-packages.php';

        try {
            $config = $this->app['files']->getRequire($path);
        }
        catch (FileNotFoundException $e) {
            $config = array();
        }

        if (is_array($config) && isset($config['packages']) && is_array($config['packages'])) {
            return array_keys($config['packages']);
        }

        return array();
    }
}
