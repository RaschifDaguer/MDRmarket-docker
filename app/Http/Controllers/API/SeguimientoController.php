<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SeguimientoController extends Controller
{
    private const FALLBACK_A    = ['lat' => -17.7761, 'lng' => -63.1952];
    private const FALLBACK_C    = ['lat' => -17.7963, 'lng' => -63.1672];
    private const TOTAL_PUNTOS  = 60;
    private const DURACION_SEG  = 120;
    private const SEG_POR_PUNTO = self::DURACION_SEG / self::TOTAL_PUNTOS;
    private const GMAP_KEY      = 'AIzaSyCNUsAhExPgVUyS_onGyhOW-VxS-gXC5Ss';

    public function obtenerRutaSimulada(int $id)
    {
        $pedido = DB::table('Pedido as p')
            ->leftJoin('Repartidor as r', 'p.id_repartidor', '=', 'r.id')
            ->leftJoin('Comerciante as c', 'p.id_comerciante', '=', 'c.id')
            ->where('p.id', $id)
            ->select(
                'p.id as pedido_id', 'p.estado', 'p.created_at',
                'p.latitud_entrega', 'p.longitud_entrega', 'p.direccion_entrega',
                'p.id_repartidor',
                'r.nombre as repartidor_nombre', 'r.telefono as repartidor_telefono',
                'r.placa as repartidor_placa', 'r.tipo as repartidor_tipo',
                'r.rating_promedio as repartidor_rating',
                'c.nombre as comerciante_nombre',
                'c.latitud as comerciante_lat', 'c.longitud as comerciante_lng',
            )
            ->first();

        if (!$pedido) {
            return response()->json(['status' => 'error', 'message' => 'Pedido no encontrado.'], 404);
        }

        // Coords origen/destino
        $latO = $this->vc($pedido->comerciante_lat) ? (float) $pedido->comerciante_lat : self::FALLBACK_A['lat'];
        $lngO = $this->vc($pedido->comerciante_lng) ? (float) $pedido->comerciante_lng : self::FALLBACK_A['lng'];
        $latD = $this->vc($pedido->latitud_entrega)  ? (float) $pedido->latitud_entrega  : self::FALLBACK_C['lat'];
        $lngD = $this->vc($pedido->longitud_entrega) ? (float) $pedido->longitud_entrega : self::FALLBACK_C['lng'];

        if (!$this->vc($pedido->comerciante_lat) && $this->vc($pedido->latitud_entrega)) {
            $latO = $latD + 0.009;
            $lngO = $lngD - 0.005;
        }

        // Ruta REAL por calles (cacheada por pedido)
        $puntosRuta = $this->obtenerRutaPorCalles($id, $latO, $lngO, $latD, $lngD);
        $totalPts   = count($puntosRuta);

        // Índice lineal basado en tiempo desde creación
        $createdAt = strtotime($pedido->created_at ?? 'now');
        $elapsed   = time() - $createdAt;
        $indice    = min((int) floor($elapsed / self::SEG_POR_PUNTO), $totalPts - 1);
        $indice    = max(0, $indice);

        $ubicacion = $puntosRuta[$indice];
        $progreso  = $totalPts > 1 ? $indice / ($totalPts - 1) : 1.0;
        $terminado = $indice >= $totalPts - 1;

        $distTotal = $this->haversineKm($latO, $lngO, $latD, $lngD);
        $distRest  = $distTotal * (1 - $progreso);
        $etaSeg    = max(0, self::DURACION_SEG - $elapsed);
        $etaMin    = (int) ceil($etaSeg / 60);

        if ($terminado || $pedido->estado === 'entregado') {
            $label = 'Entregado'; $paso = 5;
        } elseif ($progreso > 0.85) {
            $label = 'Casi llegando'; $paso = 4;
        } elseif ($progreso > 0.15) {
            $label = 'En camino hacia ti'; $paso = 4;
        } elseif ($progreso > 0.05) {
            $label = 'Recogiendo pedido'; $paso = 3;
        } else {
            $label = 'Pedido confirmado'; $paso = 1;
        }

        return response()->json([
            'status'       => 'success',
            'id_pedido'    => $pedido->pedido_id,
            'estado'       => $terminado ? 'entregado' : $pedido->estado,
            'estado_label' => $label,
            'estado_paso'  => $paso,
            'eta'          => $terminado ? '0 min' : ($etaMin > 0 ? "{$etaMin} min" : 'Llegando'),
            'progreso'     => round($progreso * 100),
            'distancia_km' => round($distRest, 1),
            'distancia_total_km' => round($distTotal, 1),
            'velocidad_kmh' => $terminado ? 0 : rand(20, 45),
            'repartidor_ubicacion' => [
                'lat'      => $ubicacion['lat'],
                'lng'      => $ubicacion['lng'],
                'nombre'   => $pedido->repartidor_nombre ?? 'Repartidor asignado',
                'telefono' => $pedido->repartidor_telefono,
                'rating'   => $pedido->repartidor_rating,
                'tipo'     => $pedido->repartidor_tipo ?? 'moto',
            ],
            'repartidor' => $pedido->id_repartidor ? [
                'nombre'   => $pedido->repartidor_nombre,
                'telefono' => $pedido->repartidor_telefono,
                'placa'    => $pedido->repartidor_placa,
                'tipo'     => $pedido->repartidor_tipo ?? 'moto',
                'rating'   => $pedido->repartidor_rating,
            ] : null,
            'origen'  => ['lat' => $latO, 'lng' => $lngO, 'nombre' => $pedido->comerciante_nombre ?? 'Comercio'],
            'destino' => ['lat' => $latD, 'lng' => $lngD, 'direccion' => $pedido->direccion_entrega],
        ], 200);
    }

    /**
     * Obtiene ruta por calles via Google Directions API y cachea por pedido.
     * Si falla, usa interpolación lineal como fallback.
     */
    private function obtenerRutaPorCalles(int $pedidoId, float $latO, float $lngO, float $latD, float $lngD): array
    {
        $cacheKey = "ruta_pedido_{$pedidoId}";

        return Cache::remember($cacheKey, 3600, function () use ($latO, $lngO, $latD, $lngD) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin'      => "{$latO},{$lngO}",
                    'destination' => "{$latD},{$lngD}",
                    'mode'        => 'driving',
                    'key'         => self::GMAP_KEY,
                ]);

                if ($response->ok()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === 'OK' && !empty($data['routes'][0]['overview_polyline']['points'])) {
                        $encoded = $data['routes'][0]['overview_polyline']['points'];
                        $decoded = $this->decodePolyline($encoded);
                        if (count($decoded) >= 2) {
                            return $this->distribuirPuntos($decoded, self::TOTAL_PUNTOS);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silenciar — usar fallback
            }

            return $this->interpolarLineal($latO, $lngO, $latD, $lngD, self::TOTAL_PUNTOS);
        });
    }

    /**
     * Decodifica polyline encoded de Google (formato estándar).
     */
    private function decodePolyline(string $encoded): array
    {
        $points = [];
        $index  = 0;
        $lat    = 0;
        $lng    = 0;
        $len    = strlen($encoded);

        while ($index < $len) {
            foreach (['lat', 'lng'] as $coord) {
                $shift  = 0;
                $result = 0;
                do {
                    $b       = ord($encoded[$index++]) - 63;
                    $result |= ($b & 0x1F) << $shift;
                    $shift  += 5;
                } while ($b >= 0x20);

                $delta = ($result & 1) ? ~($result >> 1) : ($result >> 1);
                $$coord += $delta;
            }
            $points[] = ['lat' => $lat / 1e5, 'lng' => $lng / 1e5];
        }

        return $points;
    }

    /**
     * Distribuye N puntos uniformemente a lo largo de una polyline decodificada.
     */
    private function distribuirPuntos(array $polyPts, int $n): array
    {
        if (count($polyPts) <= $n) return $polyPts;

        $dists = [];
        $total = 0;
        for ($i = 1; $i < count($polyPts); $i++) {
            $d = $this->haversineKm(
                $polyPts[$i-1]['lat'], $polyPts[$i-1]['lng'],
                $polyPts[$i]['lat'],   $polyPts[$i]['lng']
            );
            $dists[] = $d;
            $total  += $d;
        }

        if ($total == 0) return $polyPts;

        $interval = $total / $n;
        $result   = [$polyPts[0]];
        $debt     = 0;

        for ($s = 0; $s < count($dists); $s++) {
            $segDist = $dists[$s];
            $pos     = $debt > 0 ? -$debt : 0;
            $debt    = 0;

            while ($pos + $interval <= $segDist + 0.0001) {
                $pos += $interval;
                $t    = min($pos / max($segDist, 0.0001), 1.0);
                $result[] = [
                    'lat' => round($polyPts[$s]['lat'] + ($polyPts[$s+1]['lat'] - $polyPts[$s]['lat']) * $t, 7),
                    'lng' => round($polyPts[$s]['lng'] + ($polyPts[$s+1]['lng'] - $polyPts[$s]['lng']) * $t, 7),
                ];
            }
            $debt = $segDist - $pos;
        }

        $result[] = end($polyPts);
        return $result;
    }

    private function interpolarLineal(float $latO, float $lngO, float $latD, float $lngD, int $pasos): array
    {
        $pts = [];
        for ($i = 0; $i <= $pasos; $i++) {
            $t = $i / $pasos;
            $pts[] = [
                'lat' => round($latO + ($latD - $latO) * $t, 7),
                'lng' => round($lngO + ($lngD - $lngO) * $t, 7),
            ];
        }
        return $pts;
    }

    private function vc($val): bool { return $val !== null && abs((float) $val) > 0.001; }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return $R * 2 * asin(sqrt($a));
    }
}
