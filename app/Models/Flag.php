<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flag extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];

    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }
}
