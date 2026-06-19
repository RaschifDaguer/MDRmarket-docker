<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seguimiento extends Model
{
    protected $table = 'Seguimiento';

    protected $fillable = [
        'id_repartidor',
        'latitud',
        'longitud',
        'estado',
    ];

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Repartidor::class, 'id_repartidor');
    }
}
