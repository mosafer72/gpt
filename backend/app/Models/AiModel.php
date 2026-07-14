<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class AIModel extends Model
{

    protected $table="ai_models";

    protected $fillable = [

        'key',

        'name',

        'provider',

        'input_cost',

        'output_cost',

        'is_active',

    ];



    public function plans(): BelongsToMany
    {

        return $this->belongsToMany(
            Plan::class,
            'plan_model',
            'ai_model_id',
            'plan_id'
        )
        ->withPivot(
            'is_default'
        );

    }


}
