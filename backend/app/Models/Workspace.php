<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Workspace extends Model
{


    protected $fillable = [

        'user_id',

        'workspace_type_id',

        'name',

        'type',

        'started_at',

        'expires_at',

        'is_active'

    ];



    protected $casts = [

        'is_active' => 'boolean',

        'started_at' => 'datetime',

        'expires_at' => 'datetime',

    ];




    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }




    public function workspaceType()
    {
        return $this->belongsTo(
            WorkspaceType::class
        );
    }




    public function workspaceSubscriptions()
    {
        return $this->hasMany(
            WorkspaceSubscription::class
        );
    }




    /**
     * Current Active Subscription Relation
     */
    public function currentSubscription()
    {

        return $this
            ->hasOne(
                WorkspaceSubscription::class
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->latestOfMany();

    }





    /**
     * Get Current Plan Model
     */
    public function currentPlan()
    {

        return $this
            ->currentSubscription
                ?->workspacePlan;

    }
    /**
     * Active Subscription
     */

    /**
     * Current Plan
     */
    public function plan()
    {
        return $this->hasOneThrough(
            WorkspacePlan::class,
            WorkspaceSubscription::class,
            'workspace_id',
            'id',
            'id',
            'workspace_plan_id'
        )
            ->where(
                'workspace_subscriptions.is_active',
                true
            )
            ->where(
                'workspace_subscriptions.expires_at',
                '>',
                now()
            );
    }




    public function documents()
    {
        return $this->hasMany(
            Document::class
        );
    }

    public function wallet()
    {
        return $this->hasOne(
            TokenWallet::class,
            'workspace_id'
        );
    }
    public function activeSubscription()
    {
        return $this
            ->workspaceSubscriptions()
            ->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    public function tokenWallet()
    {
        return $this->hasOne(TokenWallet::class);
    }

}
