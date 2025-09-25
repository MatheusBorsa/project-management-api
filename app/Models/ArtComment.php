<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtComment extends Model
{
    protected $fillable = [
        'art_id',
        'user_id',
        'x',
        'y',
        'comment'
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
