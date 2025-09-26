<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends BaseModel
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'trial_ends_at',
        'ends_at'
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime'
    ];

    protected function getCascadeRelations()
    {
        return [];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function active()
    {
        return in_array($this->stripe_status, ['active', 'trialing']) && (!$this->ends_at || $this->ends_at->isFuture());
    }
    
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function canceled()
    {
        return $this->stripe_status === 'canceled';
    }
}
