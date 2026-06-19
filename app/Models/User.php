<?php

namespace App\Models;

use App\Models\Cliente;
use App\Models\Comerciante;
use App\Models\Repartidor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'Usuario';

    /**
     * Append virtual attributes when the model is serialized to JSON
     * This ensures `rol` is always present in API responses.
     */
    protected $appends = ['rol'];

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'id', 'id');
    }

    public function comerciante(): HasOne
    {
        return $this->hasOne(Comerciante::class, 'id', 'id');
    }

    public function repartidor(): HasOne
    {
        return $this->hasOne(Repartidor::class, 'id', 'id');
    }

    /**
     * Accessor to expose the user's role as `rol` in serialized JSON.
     */
    public function getRolAttribute(): string
    {
        if ($this->comerciante) {
            return 'comerciante';
        }

        if ($this->repartidor) {
            return 'repartidor';
        }

        if ($this->cliente) {
            return 'cliente';
        }

        return 'usuario';
    }
}
