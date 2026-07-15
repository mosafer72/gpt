<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class LlmProvider extends Model
{


    protected $table="llm_providers";

    protected $fillable = [

        'key',

        'name',

        'api_key',

        'base_url',

        'is_active',

    ];



    protected $casts = [

        // 'api_key' => 'encrypted',

        'is_active' => 'boolean',

    ];




    public function models(): HasMany
    {

        return $this->hasMany(
            AiModel::class,
            'provider_id'
        );

    }


}
