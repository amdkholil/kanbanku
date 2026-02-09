<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'column_id',
        'title',
        'description',
        'position',
        'priority',
        'status',
        'due_date',
        'start_date',
        'estimated_hours',
        'actual_hours',
        'created_by',
    ];

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function flags()
    {
        return $this->belongsToMany(Flag::class)->withTimestamps();
    }
}
