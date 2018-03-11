<?php

namespace Shared\Hooks;

use Shared\Hooks\HookDispatcher;


class Action extends HookDispatcher
{

    /**
     * Fire an action
     *
     * @param  string  $action Name of action
     * @param  array  $arguments Arguments passed to the filter
     * @return void
     */
    public function fire($action, array $arguments)
    {
        if (empty($listeners = $this->getListeners($action))) {
            return;
        }

        $this->firing[] = $hook;

        foreach ($listeners as $listener) {
            $parameters = array_slice($arguments, 0, (int) $listener['arguments']);

            call_user_func_array($listener['callback'], $parameters);
        }

        array_pop($this->firing);
    }
}
