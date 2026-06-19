<?php

namespace App\Models;

use App\Models\Calificacion;
use App\Models\Comerciante;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;
    protected $table = 'Producto';

    protected $fillable = [
        'id_comerciante',
        'id_categoria',
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'en_oferta',
        'precio_oferta',
        'id_imagen_principal',
        'rating_producto',
        'rating_general',
    ];

    protected $casts = [
        'precio'         => 'decimal:2',
        'stock'          => 'integer',
        'en_oferta'      => 'boolean',
        'precio_oferta'  => 'decimal:2',
        'rating_producto'=> 'decimal:1',
        'rating_general' => 'decimal:1',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function comerciante(): BelongsTo
    {
        return $this->belongsTo(Comerciante::class, 'id_comerciante');
    }

    public function imagenPrincipal(): BelongsTo
    {
        return $this->belongsTo(ProductoImagen::class, 'id_imagen_principal');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class, 'id_producto');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'id_producto');
    }
}
