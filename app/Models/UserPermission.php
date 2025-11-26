<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'menu', 'can_view', 'can_edit', 'can_see_amounts'];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_see_amounts' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
