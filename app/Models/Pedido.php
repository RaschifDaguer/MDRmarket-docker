<?php

namespace App\Models;

use App\Models\Cliente;
use App\Models\Comerciante;
use App\Models\HistorialPedido;
use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'Pedido';

    protected $fillable = [
        'id_cliente',
        'id_comerciante',
        'id_repartidor',
        'id_zona_envio',
        'id_ubicacion_entrega',
        'direccion_entrega',
        'latitud_entrega',
        'longitud_entrega',
        'subtotal_productos',
        'costo_envio',
        'comisiones',
        'total',
        'estado',
        'tipo_pago',
        'referencia_pago',
        'estado_pago',
        'monto_total',
        'monto_pagado',
    ];

    protected $casts = [
        'precio'        => 'decimal:2',
        'stock'         => 'integer',
        'en_oferta'     => 'boolean',
        'precio_oferta' => 'decimal:2',
        'costo_envio'   => 'decimal:2',
        'comisiones'    => 'decimal:2',
        'monto_total'   => 'decimal:2',
        'monto_pagado'  => 'decimal:2',
        'total'         => 'decimal:2',
        'latitud_entrega' => 'decimal:7',
        'longitud_entrega' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cliente');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function comerciante(): BelongsTo
    {
        return $this->belongsTo(Comerciante::class, 'id_comerciante');
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Repartidor::class, 'id_repartidor');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'id_pedido');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialPedido::class, 'id_pedido');
    }
}
