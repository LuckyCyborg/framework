<?php

namespace Modules\Chat\Providers;

use Nova\Package\Support\Providers\ModuleServiceProvider as ServiceProvider;


class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The additional provider class names.
     *
     * @var array
     */
    protected $providers = array(
        'Modules\Chat\Providers\AuthServiceProvider',
        'Modules\Chat\Providers\EventServiceProvider',
        'Modules\Chat\Providers\RouteServiceProvider',
    );


    /**
     * Bootstrap the Application Events.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__ .'/../');

        // Configure the Package.
        $this->package('Modules/Chat', 'chat', $path);

        // Bootstrap the Package.
        $path = $path .DS .'Bootstrap.php';

        $this->bootstrapFrom($path);
    }

    /**
     * Register the Chat module Service Provider.
     *
     * This service provider is a convenient place to register your modules
     * services in the IoC container. If you wish, you may make additional
     * methods or service providers to keep the code more focused and granular.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        //
    }

}
