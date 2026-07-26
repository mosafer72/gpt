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

        'is_active' => 'boolean',

    ];








    /**
     * Plans of this workspace type
     */
    public function plans()
    {

        return $this->hasMany(

            WorkspacePlan::class,

            'workspace_type_id'

        );

    }








    /**
     * Workspaces using this type
     */
    public function workspaces()
    {

        return $this->hasMany(

            Workspace::class,

            'workspace_type_id'

        );

    }




}
