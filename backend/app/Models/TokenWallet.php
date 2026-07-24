<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class TokenWallet extends Model
{


    protected $fillable = [

        'workspace_id',
        'total_tokens',
        'used_tokens',
        'remaining_tokens'

    ];

    protected $casts = [

        'total_tokens' => 'integer',

        'used_tokens' => 'integer',

        'remaining_tokens' => 'integer',

    ];
    public function workspace()
    {
        return $this->belongsTo(
            Workspace::class,
            'workspace_id'
        );
    }


}
