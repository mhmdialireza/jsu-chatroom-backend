<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'access',
        'key',
        'role_in_room'
    ];

    protected $hidden = [
        'key',
    ];
    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot('role_in_room');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
