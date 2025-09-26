<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends BaseModel
{   
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'deadline',
        'status',
        'assigned_to'
    ];

    public function getCascadeRelations()
    {
        return ['comments', 'feedbacks', 'arts'];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function histories()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function arts()
    {
        return $this->hasMany(Art::class);
    }
}
