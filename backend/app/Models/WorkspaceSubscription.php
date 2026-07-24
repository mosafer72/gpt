<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class WorkspaceSubscription extends Model
{

    use HasFactory;


    protected $fillable = [

        'user_id',

        'workspace_id',

        'workspace_plan_id',

        'started_at',

        'expires_at',

        'is_active',

        'transaction_id',

    ];



    protected $casts = [

        'started_at' => 'datetime',

        'expires_at' => 'datetime',

        'is_active' => 'boolean',

    ];




    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }



    public function isValid(): bool
    {

        return
            $this->is_active
            &&
            $this->expires_at
            &&
            $this->expires_at->isFuture();

    }
    public function workspacePlan()
    {
        return $this->belongsTo(
            WorkspacePlan::class,
            'workspace_plan_id'
        );
    }



    public function plan()
    {

        return $this->belongsTo(
            WorkspacePlan::class,
            'workspace_plan_id'
        );

    }


    public function workspace()
    {
        return $this->belongsTo(
            Workspace::class,
            'workspace_id'
        );
    }


}
