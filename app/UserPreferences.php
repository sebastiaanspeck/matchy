<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPreferences extends Model
{
    use Uuids;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'name', 'show_inactive_leagues',
    ];

    protected $casts = [
        'show_inactive_leagues' => 'boolean',
    ];

    public function getUserPreferences()
    {
        return $user_preferences = self::firstOrFail()->getAttributes();
    }
}
