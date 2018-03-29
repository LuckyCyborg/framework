<?php

namespace Modules\Broadcast\Controllers;

use Nova\Support\Facades\Auth;

use Modules\Platform\Controllers\BaseController;

use Modules\Broadcast\Events\Sample as SampleEvent;


class Sample extends BaseController
{

    public function index()
    {
        return $this->createView()->shares('title', __d('push_server', 'Broadcasting Tests'));
    }

    public function create()
    {
        $user = Auth::user();

        broadcast(new SampleEvent($user, $user->id));

        return $this->createView()->shares('title', __d('push_server', 'Create Push Notifications'));
    }
}
