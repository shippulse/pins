<?php

namespace Obelaw\Shippulse\Pins\Models;

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
        'mapper',
    ];

    protected $casts = [
        'mapper' => 'array',
    ];

    public function mapper($provider)
    {
        if ($provider) {
            return $this->mapper[$provider] ?? $this->name;
        }

        return $this->mapper;
    }

    public function setMapper($provider, $value)
    {
        $data = $this->mapper ?? [];
        $data[$provider] = $value;

        $this->mapper = $data;
        $this->save();
    }
}
