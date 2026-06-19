<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Archivo extends Model
{
    protected $table = 'Archivo';

    protected $fillable = [
        'id_usuario',
        'nombre_original',
        'nombre_servidor',
        'ruta',
        'tipo',
        'modelo',
        'modelo_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
