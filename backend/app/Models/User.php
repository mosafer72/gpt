<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'phone',
        'name',
        'verification_code',
        'verification_code_expires_at',
        'phone_verified_at',
        'role'
    ];


    protected $hidden = [
        'verification_code',
        'remember_token',
    ];


    protected $casts = [
        'phone_verified_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
    ];



    /**
     * All user workspaces
     */
    public function workspaces()
    {
        return $this->hasMany(
            Workspace::class
        );
    }




    /**
     * Current active workspace
     */
    public function currentWorkspace()
    {
        return $this
            ->hasOne(
                Workspace::class
            )
            ->where(
                'is_active',
                true
            )
            ->latestOfMany();
    }





    /**
     * Current subscription through workspace
     */
    public function currentSubscription()
    {

        return $this
            ->currentWorkspace
            ?->currentSubscription;

    }





    /**
     * Current plan through workspace
     */
    public function currentPlan()
    {

        return $this
            ->currentWorkspace
            ?->currentPlan();

    }





    /**
     * Check active subscription
     */
    public function hasActiveSubscription(): bool
    {

        $workspace = $this->currentWorkspace;


        if (!$workspace) {

            return false;

        }


        return $workspace
            ->currentSubscription
            !== null;

    }





    /**
     * Check verified phone
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null(
            $this->phone_verified_at
        );
    }





    /**
     * Verify phone
     */
    public function markPhoneAsVerified(): bool
    {

        return $this->forceFill([

            'phone_verified_at' => now(),

            'verification_code' => null,

            'verification_code_expires_at' => null,

        ])
        ->save();

    }





    /**
     * User messages
     */
    public function messages()
    {
        return $this->hasMany(
            Message::class,
            'user_id'
        );
    }





    /**
     * Count today's messages
     */
    public function todayMessagesCount(): int
    {

        return $this
            ->messages()
            ->whereDate(
                'created_at',
                today()
            )
            ->count();

    }





    /**
     * Daily message limit
     */
    public function dailyMessageLimit(): int
    {

        $plan =
            $this->currentPlan();


        return $plan
            ?->daily_message_limit
            ??
            4;

    }





    /**
     * Can send message
     */
    public function canSendMessage(): bool
    {

        return
            $this->todayMessagesCount()
            <
            $this->dailyMessageLimit();

    }





    /**
     * Token wallet
     */
    public function tokenWallet()
    {

        return $this->hasOne(
            TokenWallet::class
        );

    }





    /**
     * Available AI Models
     */
    public function availableModels()
    {

        $workspace =
            $this->currentWorkspace;


        if (!$workspace) {

            return collect();

        }



        return app(
            \App\Application\Services\WorkspaceService::class
        )
        ->models(
            $workspace
        );

    }





    /**
     * Admin check
     */
    public function isAdmin(): bool
    {

        return $this->role === 'admin';

    }


}
