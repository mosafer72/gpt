<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'user_id', 'summery','workspace_id','model_id'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

public function workspace()
{
    return $this->belongsTo(
        Workspace::class
    );
}

}
