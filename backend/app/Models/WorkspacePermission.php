<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspacePermission extends Model
{

    protected $fillable = [
        'key',
        'name',
        'description'
    ];


}
