<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_comerciante' => 'required|exists:Comerciante,id',
            'subtotal_productos' => 'required|numeric',
            'costo_envio' => 'required|numeric',
            'monto_total' => 'required|numeric',
            'tipo_pago' => 'required|string',
            'latitud_entrega' => 'required|numeric',
            'longitud_entrega' => 'required|numeric',
            'id_zona_envio' => 'nullable|integer',
            'id_ubicacion_entrega' => 'nullable|integer',
            'referencia_pago' => 'nullable|string',
            'direccion_entrega' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.id_producto' => 'required|exists:Producto,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $cliente = $user->cliente;

        if (! $cliente) {
            return response()->json([
                'status' => 'error',
                'message' => 'El usuario autenticado no es cliente.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $pedido = new Pedido();
            $pedido->id_cliente = $cliente->id;
            $pedido->id_comerciante = $validated['id_comerciante'];
            $pedido->id_repartidor = null;
            $pedido->id_zona_envio = $validated['id_zona_envio'] ?? null;
            $pedido->id_ubicacion_entrega = $validated['id_ubicacion_entrega'] ?? null;
            $pedido->direccion_entrega = $validated['direccion_entrega'] ?? null;
            $pedido->latitud_entrega = $validated['latitud_entrega'];
            $pedido->longitud_entrega = $validated['longitud_entrega'];
            $pedido->subtotal_productos = $validated['subtotal_productos'];
            $pedido->costo_envio = $validated['costo_envio'];
            $pedido->monto_total = $validated['monto_total'];
            $pedido->total = $validated['monto_total'];
            $pedido->monto_pagado = $validated['tipo_pago'] === 'tarjeta' ? $validated['monto_total'] : 0.00;
            $pedido->estado = 'confirmado';
            $pedido->tipo_pago = $validated['tipo_pago'];
            $pedido->referencia_pago = $validated['referencia_pago'] ?? null;
            $pedido->estado_pago = $validated['tipo_pago'] === 'tarjeta' ? 'completado' : 'pendiente';
            $pedido->save();

            foreach ($validated['items'] as $item) {
                $producto = Producto::where('id', $item['id_producto'])->lockForUpdate()->first();

                if (! $producto) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "El producto con id {$item['id_producto']} no existe."
                    ], 404);
                }

                if ($producto->stock < $item['cantidad']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Stock insuficiente para el producto: {$producto->nombre}. Disponibles {$producto->stock}."
                    ], 400);
                }

                PedidoItem::create([
                    'id_pedido' => $pedido->id,
                    'id_producto' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'subtotal' => $item['precio'] * $item['cantidad'],
                ]);

                $producto->decrement('stock', $item['cantidad']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Orden de compra procesada y registrada en el servidor.',
                'id_pedido' => $pedido->id,
                'estado_inicial' => $pedido->estado,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error crítico al procesar la orden en el servidor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
