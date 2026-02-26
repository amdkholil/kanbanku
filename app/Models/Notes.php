<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_favorite',
        'hide_preview_content',
        'tags',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'hide_preview_content' => 'boolean',
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
