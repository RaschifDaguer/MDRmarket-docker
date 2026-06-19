<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comerciante extends Model
{
    use HasFactory;
    protected $table = 'Comerciante';

    protected $fillable = [
        'id',
        'nombre',
        'email',
        'telefono',
        'ruc',
        'razon_social',
        'direccion',
        'descripcion',
        'foto_perfil_url',
        'banner_url',
        'latitud',
        'longitud',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_comerciante');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_comerciante');
    }
}
