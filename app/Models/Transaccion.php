<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaccion extends Model
{
    protected $table = 'transacciones';

    protected $fillable = [
        'monto',
        'tipo',
        'concepto_detalle',
        'estado_pago',
        'pedido_id',
        'comerciante_id',
        'repartidor_id',
        'cliente_id',
        'fecha_registro',
    ];

    protected $casts = [
        'monto'           => 'decimal:2',
        'fecha_registro'  => 'datetime',
    ];

    public function comerciante(): BelongsTo
    {
        return $this->belongsTo(Comerciante::class, 'comerciante_id');
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
