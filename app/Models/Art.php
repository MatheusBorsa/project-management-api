<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Art extends Model
{
    use SoftDeletes;
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
