<?php

namespace Modules\Broadcast\Controllers;

use Modules\Platform\Controllers\BaseController;


class Sample extends BaseController
{

    public function index()
    {
        return $this->createView()->shares('title', __d('push_server', 'Broadcasting Tests'));
    }

    public function create()
    {
        return $this->createView()->shares('title', __d('push_server', 'Create Push Notifications'));
    }
}
