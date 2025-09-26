<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ClientInvitation extends BaseModel
{
    use SoftDeletes;
    protected $fillable = [
        'client_id',
        'invited_by',
        'email',
        'role',
        'token',
        'status',
        'expires_at',
        'accepted_at'
    ];

    protected function getCascadeRelations()
    {
        return [];
    }

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }

    public function scopedExpired($query)
    {
        return $query->where(function($q) {
            $q->where('expires_at', '<=', now())
              ->orWhere('status', 'expired');
        });
    }

    public function isExpired()
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    public function isPending()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function accept()
    {
        return $this->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);
    }

    public function decline()
    {
        return $this->update(['status' => 'declined']);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            $invitation->token = Str::random(64);
            $invitation->expires_at = Carbon::now()->addDays(7);
        });
    }
}
