<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected function getCascadeRelations()
    {
        return [];
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            if(!$model->isForceDeleting()) {
                foreach ($model->getCascadeRelations() as $relation) {
                    if (method_exists($model, $relation)) {
                        $model->$relation()->delete();
                    }
                }
            }
        });

        static::restoring(function ($model) {
            foreach ($model->getCascadeRelations() as $relation) {
                if (method_exists($model, $relation)) {
                    $model->$relation()->withTrashed()->restore();
                }
            }
        });
    }
}