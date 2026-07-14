<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Plan extends Model
{

    use HasFactory;



    protected $fillable = [

        'name',

        'display_name',

        'price',

        'duration_days',

        'daily_message_limit',

        'token_limit',

        'capabilities',

        'features',

        'is_active'

    ];



    protected $casts = [

        'features' => 'array',

        'capabilities' => 'array',

        'is_active' => 'boolean',

    ];





    public function subscriptions(): HasMany
    {

        return $this->hasMany(
            Subscription::class
        );

    }






public function models()
{
    return $this->belongsToMany(
        AIModel::class,
        'plan_model',
        'plan_id',
        'ai_model_id'
    )
    ->withPivot(
        'is_default'
    );
}






    public function defaultModel()
    {

        return $this->models()

            ->wherePivot(

                'is_default',

                true

            )

            ->first();

    }







    public function getPriceInTomansAttribute(): int
    {

        return (int) (

            $this->price / 10

        );

    }


}
