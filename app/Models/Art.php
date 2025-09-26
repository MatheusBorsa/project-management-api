<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Art extends BaseModel
{
    use SoftDeletes;
    protected $fillable = [
        'task_id',
        'title',
        'art_path',
        'status'
    ];

    protected function getCascadeRelations()
    {
        return ['comments', 'feedbacks'];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(ArtFeedback::class);
    }

    public function comments()
    {
        return $this->hasMany(ArtComment::class);
    }
}
