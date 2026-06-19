<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPedido extends Model
{
    protected $table = 'HistorialPedido';

    protected $fillable = [
        'id_pedido',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'usuario_id',
        'usuario_rol',
        'fecha_cambio',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }
}
