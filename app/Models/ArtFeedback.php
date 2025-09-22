<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtFeedback extends Model
{
    protected $fillable = [
        'art_id',
        'user_id',
        'feedback'
    ];

    public function art()
    {
        return $this->belongsTo(Art::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
