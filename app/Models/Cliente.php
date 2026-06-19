<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;
    protected $table = 'Cliente';

    protected $fillable = [
        'id',
        'nombre',
        'email',
        'telefono',
        'direccion',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }
}
