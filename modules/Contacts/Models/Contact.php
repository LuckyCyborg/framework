<?php

namespace Modules\Contacts\Models;

use Nova\Database\ORM\Model as BaseModel;
use Nova\Support\Str;


class Contact extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'contacts';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $fillable = array('name', 'email', 'description', 'path');


    /**
     * @return \Nova\Database\ORM\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany('Modules\Contacts\Models\Message', 'contact_id');
    }

    public static function findByPath($path)
    {
        $contacts = static::all();

        foreach ($contacts as $contact) {
            if ($contact->matches($path)) {
                return $contact;
            }
        }

        return $contacts->first();
    }

    protected function matches($path)
    {
        if (empty($pattern = $this->path)) {
            $patterns = array('*');
        } else {
            $lines = explode("\n", str_replace('<front>', '/', $pattern));

            $patterns = array_filter(array_map('trim', $lines), function ($value)
            {
                return ! empty($value);
            });
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the comment count field.
     */
    public function updateCount()
    {
        $this->count = $this->messages()->count();

        $this->save();
    }
}
