<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceType extends Model
{

    protected $fillable = [

        'key',
        'name',
        'display_name',
        'description',
        'icon',
        'features',
        'is_active'

    ];


    protected $casts = [

        'features' => 'array',

        'is_active' => 'boolean'

    ];

    public function features()
    {
        return $this->belongsToMany(
            WorkspaceFeature::class
        );
    }

}
