<?php

namespace A2Workspace\SocialEntry\Entity;

use Illuminate\Database\Eloquent\Model;

class Identifier extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'social_entry_identifiers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identifier',
        'type',
    ];

    /**
     * Get the user model that the social identifier belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }
}
