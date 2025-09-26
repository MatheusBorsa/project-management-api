<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtFeedback extends BaseModel
{
    protected $fillable = [
        'art_id',
        'user_id',
        'feedback'
    ];

    protected function getCascadeRelations()
    {
        return [];
    }

    public function art()
    {
        return $this->belongsTo(Art::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
