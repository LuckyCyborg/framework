<?php

namespace Modules\Broadcast\Providers;

use Nova\Broadcasting\FactoryInterface as BroadcastManager;
use Nova\Package\Support\Providers\ModuleServiceProvider as ServiceProvider;

use Modules\Broadcast\Services\Broadcasters\PushBroadcaster;


class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The additional provider class names.
     *
     * @var array
     */
    protected $providers = array(
        'Modules\Broadcast\Providers\AuthServiceProvider',
        'Modules\Broadcast\Providers\EventServiceProvider',
        'Modules\Broadcast\Providers\RouteServiceProvider',
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
        $this->package('Modules/Broadcast', 'broadcast', $path);

        // Bootstrap the Package.
        $path = $path .DS .'Bootstrap.php';

        $this->bootstrapFrom($path);
    }

    /**
     * Register the Broadcast Module Service Provider.
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

        // Extend the Broadcasting Service.
        $broadcastManager = $this->app->make(BroadcastManager::class);

        $broadcastManager->extend('push', function ($app, array $config)
        {
            return new PushBroadcaster($app, $config);
        });
    }

}
