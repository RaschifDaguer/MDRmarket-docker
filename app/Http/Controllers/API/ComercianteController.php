<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comerciante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComercianteController extends Controller
{
    /**
     * List all merchants.
     *
     * Optional query params for proximity filtering:
     *   lat      – client latitude  (decimal)
     *   lng      – client longitude (decimal)
     *   radio_km – search radius in km (default 5.0; 0 = no limit)
     */
    public function index(Request $request)
    {
        try {
            $lat     = $request->input('lat')      !== null ? (float) $request->input('lat')     : null;
            $lng     = $request->input('lng')      !== null ? (float) $request->input('lng')     : null;
            $radioKm = $request->input('radio_km') !== null ? (float) $request->input('radio_km') : 5.0;

            $comerciantes = Comerciante::with(['productos' => function ($query) {
                $query->where('stock', '>', 0)->with('categoria');
            }])->get();

            $dataTransformada = $comerciantes->map(function ($comerciante) use ($lat, $lng) {
                $productIds = $comerciante->productos->pluck('id')->all();

                $rating = null;
                if (! empty($productIds)) {
                    $rating = DB::table('calificaciones')
                        ->whereIn('id_producto', $productIds)
                        ->avg('estrellas_producto');
                }

                $latC = $comerciante->latitud  ? (float) $comerciante->latitud  : null;
                $lngC = $comerciante->longitud ? (float) $comerciante->longitud : null;

                $distanciaKm = null;
                if ($lat !== null && $lng !== null && $latC !== null && $lngC !== null) {
                    $distanciaKm = round($this->haversineKm($lat, $lng, $latC, $lngC), 2);
                }

                return [
                    'id'              => $comerciante->id,
                    'nombreNegocio'   => $comerciante->nombre,
                    'descripcion'     => $comerciante->descripcion ?? null,
                    'direccion'       => $comerciante->direccion,
                    'latitud'         => $latC,
                    'longitud'        => $lngC,
                    'fotoPerfil'      => $comerciante->foto_perfil_url  ?? 'https://placehold.co/150.png',
                    'bannerFoto'      => $comerciante->banner_url       ?? 'https://placehold.co/600x200.png',
                    'rating'          => round(($rating ?? 4.5), 1),
                    'ventasContador'  => $comerciante->pedidos()->where('estado', 'entregado')->count(),
                    'categoriasIds'   => $comerciante->productos->pluck('id_categoria')->unique()->values()->all(),
                    'productos'       => $comerciante->productos,
                    'distancia_km'    => $distanciaKm,
                ];
            });

            // Apply proximity filter when client coordinates are provided
            if ($lat !== null && $lng !== null && $radioKm > 0) {
                $dataTransformada = $dataTransformada
                    ->filter(function ($c) use ($radioKm) {
                        // Merchants without coordinates are always included (backward compat)
                        return $c['distancia_km'] === null || $c['distancia_km'] <= $radioKm;
                    })
                    ->sortBy(fn ($c) => $c['distancia_km'] ?? PHP_INT_MAX)
                    ->values();
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Comercios recuperados exitosamente.',
                'data'    => $dataTransformada,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener los comercios: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $comerciante = Comerciante::with(['productos' => function ($query) {
            $query->where('stock', '>', 0)->with('categoria');
        }])->find($id);

        if (! $comerciante) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El comercio solicitado no existe en la base de datos.',
            ], 404);
        }

        $productIds = $comerciante->productos->pluck('id')->all();
        $rating = null;
        if (! empty($productIds)) {
            $rating = DB::table('calificaciones')
                ->whereIn('id_producto', $productIds)
                ->avg('estrellas_producto');
        }

        $dataTransformada = [
            'id'             => $comerciante->id,
            'nombreNegocio'  => $comerciante->nombre,
            'descripcion'    => $comerciante->descripcion ?? null,
            'telefono'       => $comerciante->telefono ?? null,
            'ruc'            => $comerciante->ruc ?? null,
            'razonSocial'    => $comerciante->razon_social ?? null,
            'direccion'      => $comerciante->direccion,
            'latitud'        => $comerciante->latitud  ? (float) $comerciante->latitud  : null,
            'longitud'       => $comerciante->longitud ? (float) $comerciante->longitud : null,
            'fotoPerfil'     => $comerciante->foto_perfil_url  ?? 'https://placehold.co/150.png',
            'bannerFoto'     => $comerciante->banner_url       ?? 'https://placehold.co/600x200.png',
            'rating'         => round(($rating ?? 4.5), 1),
            'ventasContador' => $comerciante->pedidos()->where('estado', 'entregado')->count(),
            'categoriasIds'  => $comerciante->productos->pluck('id_categoria')->unique()->values()->all(),
            'productos'      => $comerciante->productos,
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'Comercio recuperado exitosamente.',
            'data'    => $dataTransformada,
        ], 200);
    }

    public function update(Request $request, int $id)
    {
        $comerciante = Comerciante::find($id);

        if (! $comerciante) {
            return response()->json(['status' => 'error', 'message' => 'Comerciante no encontrado.'], 404);
        }

        if ($request->user()->id !== $id) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
        }

        $data = $request->validate([
            'nombre'          => 'nullable|string|max:255',
            'nombreNegocio'   => 'nullable|string|max:255',
            'descripcion'     => 'nullable|string',
            'telefono'        => 'nullable|string|max:50',
            'ruc'             => 'nullable|string|max:50',
            'razonSocial'     => 'nullable|string|max:255',
            'direccion'       => 'nullable|string',
            'foto_perfil_url' => 'nullable|string|max:500',
            'banner_url'      => 'nullable|string|max:500',
            'latitud'         => 'nullable|numeric|between:-90,90',
            'longitud'        => 'nullable|numeric|between:-180,180',
        ]);

        if (isset($data['nombreNegocio']) && ! isset($data['nombre'])) {
            $data['nombre'] = $data['nombreNegocio'];
        }
        unset($data['nombreNegocio']);

        if (isset($data['razonSocial'])) {
            $data['razon_social'] = $data['razonSocial'];
            unset($data['razonSocial']);
        }

        $comerciante->update(array_filter($data, fn ($v) => ! is_null($v)));

        return response()->json([
            'status'  => 'success',
            'message' => 'Comerciante actualizado exitosamente.',
            'data'    => $comerciante->fresh(),
        ], 200);
    }

    public function storeAuditLog(Request $request, int $id)
    {
        $comerciante = Comerciante::find($id);

        if (! $comerciante) {
            return response()->json(['status' => 'error', 'message' => 'Comerciante no encontrado.'], 404);
        }

        $data = $request->validate([
            'action' => 'required|string|max:255',
            'notes'  => 'nullable|string',
        ]);

        $log = DB::table('audit_logs')->insertGetId([
            'comerciante_id' => $id,
            'action'         => $data['action'],
            'notes'          => $data['notes'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Registro de auditoría guardado.',
            'data'    => ['id' => $log],
        ], 201);
    }

    public function getAuditLogs(int $id)
    {
        $comerciante = Comerciante::find($id);

        if (! $comerciante) {
            return response()->json(['status' => 'error', 'message' => 'Comerciante no encontrado.'], 404);
        }

        $logs = DB::table('audit_logs')
            ->where('comerciante_id', $id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $logs,
        ], 200);
    }

    // ── Haversine distance formula ────────────────────────────────────────────
    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371.0; // Earth radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        return $R * 2 * asin(sqrt($a));
    }
}
