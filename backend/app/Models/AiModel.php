<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;



class AiModel extends Model
{


    protected $fillable = [

        'key',

        'name',

        'model_name',

        'provider_id',

        'input_cost',

        'output_cost',

        'is_active',

    ];




    public function provider(): BelongsTo
    {

        return $this->belongsTo(
            LlmProvider::class
        );

    }




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
