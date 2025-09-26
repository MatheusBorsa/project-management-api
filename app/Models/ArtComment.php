<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArtComment extends BaseModel
{
    use SoftDeletes;
    protected $fillable = [
        'art_id',
        'user_id',
        'x',
        'y',
        'comment'
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
