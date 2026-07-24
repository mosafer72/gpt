<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceFeature extends Model
{

    protected $fillable = [
        'key',
        'name',
        'description'
    ];


    public function workspaceTypes()
    {
        return $this->belongsToMany(
            WorkspaceType::class
        );
    }



}
