<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class WorkspaceSubscription extends Model
{

    protected $fillable = [

        'user_id',
        'workspace_id',
        'workspace_plan_id',
        'started_at',
        'expires_at',
        'is_active',
        'transaction_id'

    ];



    protected $casts = [

        'started_at'=>'datetime',
        'expires_at'=>'datetime',
        'is_active'=>'boolean'

    ];



    public function workspace()
    {
        return $this->belongsTo(
            Workspace::class
        );
    }



    public function workspacePlan()
    {
        return $this->belongsTo(
            WorkspacePlan::class,
            'workspace_plan_id'
        );
    }



    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

}
