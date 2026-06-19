<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    // Franjas de precio en Bs según distancia en km
    private const TIERS = [
        ['max_km' => 2,  'costo' => 5.0,  'label' => 'Corta distancia (≤ 2 km)'],
        ['max_km' => 5,  'costo' => 10.0, 'label' => 'Mediana distancia (2–5 km)'],
        ['max_km' => PHP_INT_MAX, 'costo' => 15.0, 'label' => 'Larga distancia (> 5 km)'],
    ];

    // -------------------------------------------------------------------------
    // GET /api/calcular-envio
    // Parámetros: lat_origen, lng_origen, lat_destino, lng_destino
    //         OR: id_comerciante, lat_destino, lng_destino
    // -------------------------------------------------------------------------
    public function calcular(Request $request)
    {
        $request->validate([
            'lat_destino'    => 'required|numeric',
            'lng_destino'    => 'required|numeric',
            'lat_origen'     => 'nullable|numeric',
            'lng_origen'     => 'nullable|numeric',
            'id_comerciante' => 'nullable|integer|exists:Comerciante,id',
        ]);

        $latDest = (float) $request->lat_destino;
        $lngDest = (float) $request->lng_destino;

        // Origen: coordenadas explícitas o las almacenadas del comerciante
        if ($request->filled('lat_origen') && $request->filled('lng_origen')) {
            $latOrigen = (float) $request->lat_origen;
            $lngOrigen = (float) $request->lng_origen;
        } elseif ($request->filled('id_comerciante')) {
            $comerciante = DB::table('Comerciante')
                ->where('id', $request->id_comerciante)
                ->select('latitud', 'longitud')
                ->first();

            if (! $comerciante || is_null($comerciante->latitud)) {
                // Sin coordenadas del comercio: devolver costo base
                return response()->json([
                    'status'       => 'success',
                    'costo_envio'  => 10.0,
                    'distancia_km' => null,
                    'franja'       => 'Mediana distancia (sin GPS del comercio)',
                ], 200);
            }

            $latOrigen = (float) $comerciante->latitud;
            $lngOrigen = (float) $comerciante->longitud;
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Debes enviar lat_origen/lng_origen o id_comerciante.',
            ], 422);
        }

        $distanciaKm = $this->haversine($latOrigen, $lngOrigen, $latDest, $lngDest);
        [$costo, $franja] = $this->franjaYCosto($distanciaKm);

        return response()->json([
            'status'       => 'success',
            'costo_envio'  => $costo,
            'distancia_km' => round($distanciaKm, 2),
            'franja'       => $franja,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Método estático para usar internamente desde otros controladores
    // -------------------------------------------------------------------------
    public static function calcularCosto(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): float
    {
        if (is_null($lat1) || is_null($lng1) || is_null($lat2) || is_null($lng2)) {
            return 10.0; // costo base si no hay coordenadas
        }

        $self = new self();
        $km   = $self->haversine($lat1, $lng1, $lat2, $lng2);
        [$costo] = $self->franjaYCosto($km);

        return $costo;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371; // radio de la Tierra en km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function franjaYCosto(float $km): array
    {
        foreach (self::TIERS as $tier) {
            if ($km <= $tier['max_km']) {
                return [$tier['costo'], $tier['label']];
            }
        }
        return [15.0, 'Larga distancia'];
    }
}
