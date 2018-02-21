<?php

namespace App\Providers;

use Nova\Support\Facades\Config;
use Nova\Support\ServiceProvider;

use InvalidArgumentException;


class ThemeServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to all Theme Service Providers.
     *
     * @var string
     */
    protected $namespace = 'Themes';


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
        $namespace = trim(
            Config::get('view.themes.namespace'), '\\'
        );

        $themes = $this->getInstalledThemes();

        $themes->each(function ($theme) use ($namespace)
        {
            $provider = sprintf('%s\\%s\\Providers\\ThemeServiceProvider', $namespace, $theme);

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        });
    }

    protected function getInstalledThemes()
    {
        $themesPath = Config::get('view.themes.path');

        try {
            $paths = $this->app['files']->directories($themesPath);
        }
        catch (InvalidArgumentException $e) {
            $paths = array();
        }

        $themes = array_map(function ($path)
        {
            return basename($path);

        }, $paths);

        return collect($themes);
    }
}
