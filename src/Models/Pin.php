<?php

namespace Shipperways\Pins\Models;

use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'type',
    ];
}
