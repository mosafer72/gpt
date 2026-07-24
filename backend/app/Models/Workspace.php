<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Workspace extends Model
{

    protected $fillable = [

        'user_id',

        'workspace_type_id',

        'workspace_plan_id',

        'name',

        'type',

        'started_at',

        'expires_at',

        'is_active'

    ];



    protected $casts = [

        'started_at' => 'datetime',

        'expires_at' => 'datetime',

        'is_active' => 'boolean'

    ];



    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }



    public function currentSubscription()
    {
        return $this
            ->workspaceSubscriptions()
            ->where('is_active', true)
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->with('workspacePlan')
            ->first();
    }


    public function workspaceSubscriptions()
    {
        return $this->hasMany(
            WorkspaceSubscription::class,
            'workspace_id'
        );
    }



    public function wallet()
    {
        return $this->hasOne(
            TokenWallet::class,
            'workspace_id'
        );
    }

    public function plan()
    {
        return $this->belongsTo(
            WorkspacePlan::class,
            'workspace_plan_id'
        );
    }

    public function usages()
    {
        return $this->hasMany(
            Usage::class,
            'workspace_id'
        );
    }
    public function workspaceType()
    {
        return $this->belongsTo(
            WorkspaceType::class,
            'workspace_type_id'
        );
    }
    public function activeSubscription()
    {
        return $this
            ->workspaceSubscriptions()
            ->where('is_active', true)
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->first();
    }
    public function getPlanAttribute()
    {
        return $this
            ->workspaceSubscriptions
            ->first()
                ?->workspacePlan;
    }
    public function documents()
    {
        return $this->hasMany(
            Document::class
        );
    }

}
