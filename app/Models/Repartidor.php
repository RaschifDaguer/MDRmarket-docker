<?php

namespace App\Models;

use App\Models\Seguimiento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repartidor extends Model
{
    use HasFactory;
    protected $table = 'Repartidor';

    protected $fillable = [
        'id',
        'nombre',
        'email',
        'telefono',
        'placa',
        'tipo',
        'rating_promedio',
        'total_calificaciones',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_repartidor');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class, 'id_repartidor');
    }
}
