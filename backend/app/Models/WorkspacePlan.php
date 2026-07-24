<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class WorkspacePlan extends Model
{


    protected $fillable = [
        'name',
        'display_name',
        'workspace_type_id',
        'description',
        'price',
        'duration_days',
        'token_limit',
        'features'

    ];


    protected $casts = [

        'features' => 'array'

    ];


    public function workspaces()
    {

        return $this->hasMany(
            Workspace::class
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
    public function workspaceType()
    {

        return $this->belongsTo(
            WorkspaceType::class,
            'workspace_type_id'
        );

    }
    public function permissions()
    {

        return $this->belongsToMany(
            WorkspacePermission::class
        );

    }



}
