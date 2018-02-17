<?php

namespace Modules\Users\Models;

use Nova\Auth\UserTrait;
use Nova\Auth\UserInterface;
use Nova\Database\ORM\Model as BaseModel;
use Nova\Foundation\Auth\Access\AuthorizableTrait;
use Nova\Support\Facades\Cache;

use Shared\Auth\Reminders\RemindableTrait;
use Shared\Auth\Reminders\RemindableInterface;
use Shared\Notifications\NotifiableTrait;

use Modules\Attachments\Traits\HasAttachmentsTrait;
use Modules\Fields\Traits\MetableTrait;
use Modules\Messages\Traits\HasMessagesTrait;
use Modules\Platform\Traits\HasActivitiesTrait;
use Modules\Users\Models\Profile;


class User extends BaseModel implements UserInterface, RemindableInterface
{
    use UserTrait, RemindableTrait, AuthorizableTrait, MetableTrait, NotifiableTrait, HasActivitiesTrait, HasMessagesTrait, HasAttachmentsTrait;

    //
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $fillable = array('username', 'password', 'email', 'image', 'profile_id');

    protected $hidden = array('password', 'remember_token');

    // Setup the Metadata.
    protected $with = array('meta');

    protected $metaTable   = 'users_meta';
    protected $metaKeyName = 'user_id';

    // Caches.
    protected $cachedFields;
    protected $cachedRoles;
    protected $cachedPermissions;


    public function profile()
    {
        return $this->belongsTo('Modules\Users\Models\Profile', 'profile_id');
    }

    public function getMetaFields()
    {
        if (isset($this->cachedFields)) {
            return $this->cachedFields;
        }

        // The fields are not cached.
        else if ($this->exists) {
            $this->load('profile');

            return $this->cachedFields = $this->profile->fields;
        } else {
            $profile = Profile::findOrFail(1);

            return $this->cachedFields = $profile->fields;
        }
    }

    public function realname()
    {
        return trim($this->meta->first_name .' ' .$this->meta->last_name);
    }

    public function picture()
    {
        $path = 'assets/images/pictures/';

        $picture = $this->meta->picture;

        if (! empty($picture) && is_readable(BASEPATH .($path .= basename($picture)))) {
            // Nothing to do.
        } else {
            // Fallback to the default image.
            $path = 'assets/images/users/no-image.png';
        }

        return site_url($path);
    }

    /**
     * Roles and Permissions (ACL)
     */

    public function roles()
    {
        return $this->belongsToMany('Modules\Roles\Models\Role', 'role_user', 'user_id', 'role_id');
    }

    public function hasRole($role, $strict = false)
    {
        if (in_array('root', $roles = $this->getCachedRoles()) && ! $strict) {
            // The ROOT can impersonate any Role.
            return true;
        }

        return (bool) count(array_intersect($roles, (array) $role));
    }

    public function hasPermission($permission)
    {
        $permissions = is_array($permission) ? $permission : func_get_args();

        if (($this->getKey() === 1) || in_array('root', $this->getCachedRoles())) {
            // The USER ONE and all ROOT users are allowed for all permissions.
            return true;
        }

        return (bool) count(array_intersect($permissions, $this->getCachedPermissions()));
    }

    protected function getCachedRoles()
    {
        if (isset($this->cachedRoles)) {
            return $this->cachedRoles;
        }

        $cacheKey = 'user.roles.' .$this->getKey();

        return $this->cachedRoles = Cache::remember($cacheKey, 1440, function ()
        {
            return $this->roles->lists('slug');
        });
    }

    protected function getCachedPermissions()
    {
        if (isset($this->cachedPermissions)) {
            return $this->cachedPermissions;
        }

        $cacheKey = 'user.permissions.' .$this->getKey();

        return $this->cachedPermissions = Cache::remember($cacheKey, 1440, function ()
        {
            return $this->roles->load('permissions')->pluck('permissions')->lists('slug');
        });
    }
}
