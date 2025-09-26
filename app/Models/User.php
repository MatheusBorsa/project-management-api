<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;
use App\Traits\PhoneTrait;

class User extends BaseModel
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, PhoneTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password'
    ];

    protected function getCascadeRelations()
    {
        return ['tasks', 'comments', 'feedbacks', 'clients', 'subscriptions'];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class
        ];
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isClientOf($clientId): bool
    {
        return $this->clients()->where('client_id', $clientId)->exists();
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function taskHistories()
    {
        return $this->hasMany(TaskHistory::class, 'changed_by');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

        public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function subscribed()
    {
        return $this->subscription && $this->subscription->active();
    }

    public function onTrial()
    {
        return $this->subscription && $this->subscription->onTrial();
    }

    public function isPremium(): bool
    {
        return $this->subscribed() || $this->onTrial();
    }
}
