<?php

namespace Modules\Broadcast\Events;

use Nova\Broadcasting\Channel;
use Nova\Broadcasting\PrivateChannel;
use Nova\Broadcasting\PresenceChannel;
use Nova\Broadcasting\ShouldBroadcastInterface;

use Nova\Broadcasting\InteractsWithSocketsTrait;
use Nova\Foundation\Events\DispatchableTrait;
use Nova\Queue\SerializesModelsTrait;

use Nova\Support\Facades\Auth;

use Modules\Users\Models\User;


class Sample implements ShouldBroadcastInterface
{
    use DispatchableTrait, InteractsWithSocketsTrait, SerializesModelsTrait;

    /**
     * @var Modules\Users\Models\User
     */
    public $user;

    /**
     * @var int
     */
    private $userId;


    /**
     * Create a new Event instance.
     *
     * @return void
     */
    public function __construct(User $user, $userId)
    {
        $this->user = $user;

        $this->userId = $userId;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('Modules.Users.Models.User.' .$this->userId);
    }
}
