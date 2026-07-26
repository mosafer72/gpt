<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class WorkspacePlan extends Model
{


    protected $fillable = [

        'workspace_type_id',
        'name',
        'display_name',
        'description',
        'price',
        'duration_days',
        'token_limit',
        'features',
        'capabilities',
        'is_active'

    ];



    protected $casts = [

        'features'=>'array',
        'capabilities'=>'array',
        'is_active'=>'boolean',

    ];




    public function workspaceType()
    {
        return $this->belongsTo(
            WorkspaceType::class
        );
    }





    public function models()
    {
        return $this->belongsToMany(
            AiModel::class,
            'workspace_plan_models',
            'workspace_plan_id',
            'ai_model_id'
        );
    }





    public function subscriptions()
    {
        return $this->hasMany(
            WorkspaceSubscription::class
        );
    }



}
