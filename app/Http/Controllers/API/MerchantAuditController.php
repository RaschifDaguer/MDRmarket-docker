<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;

class MerchantAuditController extends Controller
{
    private const COMISION_PORCENTAJE = 8;

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'No autenticado'], 401);
            }

            $comercianteId = $request->query('comerciante_id', $user->id);

            $pedidos = Pedido::where('id_comerciante', $comercianteId)
                ->where('estado', 'entregado');

            $ventasBrutas     = (clone $pedidos)->sum('subtotal_productos');
            $comisionTotal    = round($ventasBrutas * self::COMISION_PORCENTAJE / 100, 2);
            $balanceNeto      = round($ventasBrutas - $comisionTotal, 2);
            $totalPedidos     = (clone $pedidos)->count();

            $ventasMes = (clone $pedidos)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('subtotal_productos');
            $comisionMes = round($ventasMes * self::COMISION_PORCENTAJE / 100, 2);
            $balanceMes  = round($ventasMes - $comisionMes, 2);

            $filtroEstado = $request->query('estado_liquidacion');
            $query = Pedido::where('id_comerciante', $comercianteId)
                ->where('estado', 'entregado')
                ->orderBy('created_at', 'desc');

            if ($filtroEstado === 'transferido') {
                $query->where('estado_pago', 'completado');
            } elseif ($filtroEstado === 'pendiente') {
                $query->where(function ($q) {
                    $q->where('estado_pago', '!=', 'completado')
                      ->orWhereNull('estado_pago');
                });
            }

            $perPage   = (int) $request->query('por_pagina', 15);
            $perPage   = max(5, min($perPage, 50));
            $paginated = $query->paginate($perPage, ['*'], 'pagina');

            $items = collect($paginated->items())->map(function ($p) {
                $subtotal  = (float) $p->subtotal_productos;
                $comision  = round($subtotal * self::COMISION_PORCENTAJE / 100, 2);
                $neto      = round($subtotal - $comision, 2);
                $liquidado = $p->estado_pago === 'completado';

                return [
                    'pedido_id'          => $p->id,
                    'fecha'              => $p->created_at?->toIso8601String(),
                    'subtotal_productos' => $subtotal,
                    'comision_app'       => $comision,
                    'monto_neto'         => $neto,
                    'estado_liquidacion' => $liquidado ? 'transferido' : 'pendiente',
                    'tipo_pago'          => $p->tipo_pago,
                ];
            });

            return response()->json([
                'status'  => 'success',
                'resumen' => [
                    'ventas_brutas'        => round($ventasBrutas, 2),
                    'comision_plataforma'  => $comisionTotal,
                    'balance_neto'         => $balanceNeto,
                    'total_pedidos'        => $totalPedidos,
                    'comision_porcentaje'  => self::COMISION_PORCENTAJE,
                    'ventas_mes'           => round($ventasMes, 2),
                    'comision_mes'         => $comisionMes,
                    'balance_mes'          => $balanceMes,
                ],
                'pedidos'        => $items,
                'total'          => $paginated->total(),
                'pagina_actual'  => $paginated->currentPage(),
                'ultima_pagina'  => $paginated->lastPage(),
                'por_pagina'     => $paginated->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener auditoría: ' . $e->getMessage(),
            ], 500);
        }
    }
}
