<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Art extends Model
{
    protected $fillable = [
        'task_id',
        'title',
        'art_path',
        'status'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(ArtFeedback::class);
    }
}
