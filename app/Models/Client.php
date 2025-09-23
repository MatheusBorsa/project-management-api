<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'notes',
        'cnpj',
        'bussiness_address',
        'website_url',
        'instagram_url',
        'linkedin_url',
        'twitter_url',
        'tiktok_url',
        'status'
    ];
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'client_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
