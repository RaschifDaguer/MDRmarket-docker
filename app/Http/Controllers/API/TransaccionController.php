<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaccion;
use Illuminate\Http\Request;

class TransaccionController extends Controller
{
    /**
     * List transactions with optional filters.
     *
     * GET /transacciones
     * Query params: tipo (ingreso|egreso), buscar (text), pagina, por_pagina
     */
    public function index(Request $request)
    {
        try {
            $query = Transaccion::query()->orderBy('fecha_registro', 'desc');

            if ($request->filled('tipo') && in_array($request->tipo, ['ingreso', 'egreso'])) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->filled('buscar')) {
                $query->where('concepto_detalle', 'like', '%' . $request->buscar . '%');
            }

            if ($request->filled('estado_pago')) {
                $query->where('estado_pago', $request->estado_pago);
            }

            $perPage = (int) $request->get('por_pagina', 20);
            $perPage = max(5, min($perPage, 100));

            $paginated = $query->paginate($perPage, ['*'], 'pagina');

            return response()->json([
                'status'       => 'success',
                'data'         => $paginated->items(),
                'total'        => $paginated->total(),
                'pagina_actual' => $paginated->currentPage(),
                'ultima_pagina' => $paginated->lastPage(),
                'por_pagina'   => $paginated->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener transacciones: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executive financial summary.
     *
     * GET /transacciones/resumen
     */
    public function resumen()
    {
        try {
            $base = Transaccion::where('estado_pago', 'completado');

            $totalIngresos = (clone $base)->where('tipo', 'ingreso')->sum('monto');
            $totalEgresos  = (clone $base)->where('tipo', 'egreso')->sum('monto');
            $balance       = $totalIngresos - $totalEgresos;

            $ingresosMes = (clone $base)
                ->where('tipo', 'ingreso')
                ->whereYear('fecha_registro',  now()->year)
                ->whereMonth('fecha_registro', now()->month)
                ->sum('monto');

            $egresosMes = (clone $base)
                ->where('tipo', 'egreso')
                ->whereYear('fecha_registro',  now()->year)
                ->whereMonth('fecha_registro', now()->month)
                ->sum('monto');

            $conteoIngresos = Transaccion::where('tipo', 'ingreso')->count();
            $conteoEgresos  = Transaccion::where('tipo', 'egreso')->count();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'balance_total'   => round($balance, 2),
                    'total_ingresos'  => round($totalIngresos, 2),
                    'total_egresos'   => round($totalEgresos, 2),
                    'ingresos_mes'    => round($ingresosMes, 2),
                    'egresos_mes'     => round($egresosMes, 2),
                    'balance_mes'     => round($ingresosMes - $egresosMes, 2),
                    'conteo_ingresos' => $conteoIngresos,
                    'conteo_egresos'  => $conteoEgresos,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener resumen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new transaction (internal / admin use).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'monto'            => 'required|numeric|min:0.01',
            'tipo'             => 'required|in:ingreso,egreso',
            'concepto_detalle' => 'required|string|max:300',
            'estado_pago'      => 'nullable|in:completado,pendiente,fallido',
            'pedido_id'        => 'nullable|integer',
            'comerciante_id'   => 'nullable|integer|exists:Comerciante,id',
            'repartidor_id'    => 'nullable|integer|exists:Repartidor,id',
            'cliente_id'       => 'nullable|integer|exists:Cliente,id',
            'fecha_registro'   => 'nullable|date',
        ]);

        $tx = Transaccion::create($data);

        return response()->json([
            'status' => 'success',
            'data'   => $tx,
        ], 201);
    }
}
