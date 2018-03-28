<?php

namespace Modules\Broadcast\Providers;

use Nova\Auth\Access\GateInterface as Gate;
use Nova\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the module.
     *
     * @var array
     */
    protected $policies = array(
        'Modules\Broadcast\Models\SomeModel' => 'Modules\Broadcast\Policies\ModelPolicy',
    );


    /**
     * Register any module authentication / authorization services.
     *
     * @param  \Nova\Auth\Access\GateInterface  $gate
     * @return void
     */
    public function boot(Gate $gate)
    {
        $this->registerPolicies($gate);

        //
    }
}
