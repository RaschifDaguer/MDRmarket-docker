<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\HistorialPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        // 1. Mapeamos el ordenamiento (soporte camelCase de Flutter)
        $requestedOrderBy = $request->get('orderBy', 'created_at');

        // Mapear 'fechaCreacion' (camelCase enviado por Flutter) a 'created_at'
        if ($requestedOrderBy === 'fechaCreacion') {
            $requestedOrderBy = 'created_at';
        }

        $allowedColumns = ['id', 'created_at', 'estado', 'subtotal_productos'];
        $orderBy = in_array($requestedOrderBy, $allowedColumns) ? $requestedOrderBy : 'created_at';
        $order = in_array($request->get('order'), ['asc', 'desc']) ? $request->get('order') : 'desc';

        // 2. Definimos cuántos elementos queremos por página
        $perPage = max(1, min($request->query('perPage', 10), 100)); // Entre 1 y 100, por defecto 10

        // 3. Construimos la query base
        $query = Pedido::with(['cliente', 'comerciante', 'repartidor', 'items.producto']);
        $user = $request->user();

        $hasExplicitFilter = $request->filled('clienteId') || $request->filled('comercianteId') || $request->filled('repartidorId');

        if ($hasExplicitFilter) {
            if ($request->filled('clienteId')) {
                $query->where('id_cliente', $request->clienteId);
            }
            if ($request->filled('comercianteId')) {
                $query->where('id_comerciante', $request->comercianteId);
            }
            if ($request->filled('repartidorId')) {
                $query->where('id_repartidor', $request->repartidorId);
            }
        } elseif ($user) {
            if ($user->comerciante) {
                $query->where('id_comerciante', $user->id);
            } elseif ($user->repartidor) {
                $query->where('id_repartidor', $user->id);
            } elseif ($user->cliente) {
                $query->where('id_cliente', $user->id);
            }
        }

        // 4. Ordenamos y paginamos
        $pedidos = $query->orderBy($orderBy, $order)
                         ->paginate($perPage);

        // Al retornar un paginador, Laravel inyecta meta-datos clave automáticamente:
        // current_page, data (los registros), total, last_page, per_page, etc.
        return response()->json($pedidos, 200);
    }

    public function show(Request $request, $id)
    {
        $pedido = Pedido::with([
                            'items.producto.imagenes',
                            'items.producto.comerciante', // needed for store coordinates
                            'cliente',
                            'comerciante',
                            'repartidor',
                            'historial',
                        ])->findOrFail($id);

        $user = $request->user();
        if ($user) {
            if ($user->cliente && $pedido->id_cliente !== $user->id) {
                return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
            }
            if ($user->comerciante && $pedido->id_comerciante !== $user->id) {
                return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
            }
            if ($user->repartidor && $pedido->id_repartidor !== $user->id) {
                return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pedido->id,
                'id_cliente' => $pedido->id_cliente,
                'id_comerciante' => $pedido->id_comerciante,
                'id_repartidor' => $pedido->id_repartidor,
                'repartidor' => $pedido->repartidor ? [
                    'id' => $pedido->repartidor->id,
                    'nombre' => $pedido->repartidor->nombre,
                    'tipo' => $pedido->repartidor->tipo,
                    'placa' => $pedido->repartidor->placa,
                ] : null,
                'estado' => $pedido->estado,
                'estado_pago' => $pedido->estado_pago,
                'tipo_pago' => $pedido->tipo_pago,
                'referencia_pago' => $pedido->referencia_pago,
                'subtotal_productos' => $pedido->subtotal_productos,
                'costo_envio' => $pedido->costo_envio,
                'monto_total' => $pedido->monto_total,
                'monto_pagado' => $pedido->monto_pagado,
                'direccion_entrega' => $pedido->direccion_entrega,
                'latitud_entrega' => $pedido->latitud_entrega ? (float) $pedido->latitud_entrega : null,
                'longitud_entrega' => $pedido->longitud_entrega ? (float) $pedido->longitud_entrega : null,
                'items' => $pedido->items->map(function ($item) {
                    return [
                        'id'          => $item->id,
                        'id_producto' => $item->id_producto,
                        'cantidad'    => $item->cantidad,
                        'precio'      => $item->precio,
                        'subtotal'    => $item->subtotal,
                        'producto'    => [
                            'id'     => $item->producto->id,
                            'nombre' => $item->producto->nombre,
                            'imagen' => optional($item->producto->imagenPrincipal)->url
                                ?? optional($item->producto->imagenes->first())->url
                                ?? null,
                        ],
                    ];
                }),
                // Unique stores involved in the order with their GPS coordinates.
                // Used by the Flutter tracking screen to populate Routes API waypoints (B).
                'tiendas' => (function () use ($pedido) {
                    $stores = $pedido->items
                        ->map(fn ($item) => $item->producto?->comerciante)
                        ->filter(fn ($c) => $c && $c->latitud && $c->longitud)
                        ->unique('id')
                        ->values()
                        ->map(fn ($c) => [
                            'id'       => $c->id,
                            'nombre'   => $c->nombre,
                            'latitud'  => (float) $c->latitud,
                            'longitud' => (float) $c->longitud,
                        ]);
                    if ($stores->isEmpty() && $pedido->comerciante && $pedido->comerciante->latitud && $pedido->comerciante->longitud) {
                        $c = $pedido->comerciante;
                        $stores = collect([[
                            'id'       => $c->id,
                            'nombre'   => $c->nombre,
                            'latitud'  => (float) $c->latitud,
                            'longitud' => (float) $c->longitud,
                        ]]);
                    }
                    return $stores;
                })(),
                'historial' => $pedido->historial->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'estado_anterior' => $h->estado_anterior,
                        'estado_nuevo' => $h->estado_nuevo,
                        'motivo' => $h->motivo,
                        'usuario_rol' => $h->usuario_rol,
                        'fecha_cambio' => $h->fecha_cambio,
                    ];
                }),
                'created_at' => $pedido->created_at,
                'updated_at' => $pedido->updated_at,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $payload = $this->buildOrderPayload($request, false);

        $validator = Validator::make($payload, [
            'id_comerciante' => 'required|exists:Comerciante,id',
            'id_repartidor' => 'required|exists:Repartidor,id',
            'id_zona_envio' => 'required|integer',
            'id_ubicacion_entrega' => 'nullable|integer',
            'direccion_entrega' => 'nullable|string|max:255',
            'latitud_entrega' => 'nullable|numeric',
            'longitud_entrega' => 'nullable|numeric',
            'subtotal_productos' => 'required|numeric|min:0',
            'costo_envio' => 'required|numeric|min:0',
            'comisiones' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id_producto' => 'required|exists:Producto,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $cliente = $request->user()->cliente;

        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'El usuario autenticado no es cliente'], 403);
        }

        DB::beginTransaction();

        try {
            $comisiones = $data['comisiones'] ?? 0.00;
            $total = $data['total'] ?? ($data['subtotal_productos'] + $data['costo_envio'] + $comisiones);

            $pedido = Pedido::create([
                'id_cliente' => $cliente->id,
                'id_comerciante' => $data['id_comerciante'],
                'id_repartidor' => $data['id_repartidor'],
                'id_zona_envio' => $data['id_zona_envio'],
                'id_ubicacion_entrega' => $data['id_ubicacion_entrega'] ?? null,
                'direccion_entrega' => $data['direccion_entrega'] ?? null,
                'latitud_entrega' => $data['latitud_entrega'] ?? null,
                'longitud_entrega' => $data['longitud_entrega'] ?? null,
                'subtotal_productos' => $data['subtotal_productos'],
                'costo_envio' => $data['costo_envio'],
                'comisiones' => $comisiones,
                'total' => $total,
                'estado' => 'pendiente',
            ]);

            foreach ($data['items'] as $item) {
                $producto = Producto::where('id', $item['id_producto'])->lockForUpdate()->first();

                if (! $producto) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "El producto con ID {$item['id_producto']} no existe en el catálogo."], 404);
                }

                if ($producto->stock < $item['cantidad']) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Stock insuficiente para el producto: {$producto->nombre}. Quedan disponibles: {$producto->stock} unidades."], 400);
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

            return response()->json(['success' => true, 'message' => 'Pedido generado exitosamente.', 'id_pedido' => $pedido->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error crítico al procesar la orden: ' . $e->getMessage()], 500);
        }
    }

    public function storeCompleto(Request $request)
    {
        $payload = $this->buildOrderPayload($request, true);

        $validator = Validator::make($payload, [
            'id_comerciante' => 'required|exists:Comerciante,id',
            'id_repartidor' => 'nullable|exists:Repartidor,id',
            'id_zona_envio' => 'required|integer',
            'id_ubicacion_entrega' => 'nullable|integer',
            'direccion_entrega' => 'nullable|string|max:255',
            'latitud_entrega' => 'nullable|numeric',
            'longitud_entrega' => 'nullable|numeric',
            'subtotal_productos' => 'required|numeric|min:0',
            'costo_envio' => 'nullable|numeric|min:0',
            'comisiones' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id_producto' => 'required|exists:Producto,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'nullable|numeric|min:0',
            'tipo_pago' => 'required|in:efectivo,qr,transferencia,tarjeta',
            'referencia_pago' => 'nullable|string|max:255',
            'estado_pago' => 'nullable|in:pendiente,completado,rechazado',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $cliente = $request->user()->cliente;

        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'El usuario autenticado no es cliente'], 403);
        }

        DB::beginTransaction();

        try {
            $costoEnvio = $data['costo_envio'] ?? $this->calculateShippingCost($data['latitud_entrega'], $data['longitud_entrega']);
            $comisiones = $data['comisiones'] ?? 0.00;
            $total = $data['total'] ?? ($data['subtotal_productos'] + $costoEnvio + $comisiones);
            $estado_pago = $data['estado_pago'] ?? 'pendiente';
            $monto_pagado = $data['monto_pagado'] ?? ($estado_pago === 'completado' ? $total : 0);

            if ($monto_pagado > $total) {
                $monto_pagado = $total;
            }

            $pedido = Pedido::create([
                'id_cliente' => $cliente->id,
                'id_comerciante' => $data['id_comerciante'],
                'id_repartidor' => $data['id_repartidor'] ?? null,
                'id_zona_envio' => $data['id_zona_envio'],
                'id_ubicacion_entrega' => $data['id_ubicacion_entrega'] ?? null,
                'direccion_entrega' => $data['direccion_entrega'] ?? null,
                'latitud_entrega' => $data['latitud_entrega'] ?? null,
                'longitud_entrega' => $data['longitud_entrega'] ?? null,
                'subtotal_productos' => $data['subtotal_productos'],
                'costo_envio' => $costoEnvio,
                'comisiones' => $comisiones,
                'total' => $total,
                'estado' => 'pendiente',
                'tipo_pago' => $data['tipo_pago'],
                'referencia_pago' => $data['referencia_pago'] ?? null,
                'estado_pago' => $estado_pago,
                'monto_total' => $total,
                'monto_pagado' => $monto_pagado,
            ]);

            foreach ($data['items'] as $item) {
                $producto = Producto::where('id', $item['id_producto'])->lockForUpdate()->first();

                if (! $producto) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "El producto con ID {$item['id_producto']} no existe en el catálogo."], 404);
                }

                if ($producto->stock < $item['cantidad']) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Stock insuficiente para el producto: {$producto->nombre}. Quedan disponibles: {$producto->stock} unidades."], 400);
                }

                $precioItem = $item['precio'] ?? $producto->precio;
                $subtotalItem = $precioItem * $item['cantidad'];

                PedidoItem::create([
                    'id_pedido' => $pedido->id,
                    'id_producto' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $precioItem,
                    'subtotal' => $subtotalItem,
                ]);

                $producto->decrement('stock', $item['cantidad']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pedido generado exitosamente.', 'data' => $pedido->load(['cliente', 'comerciante', 'repartidor', 'items.producto'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error crítico al procesar la orden: ' . $e->getMessage()], 500);
        }
    }

    public function agregarDetalle(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $data = $request->validate([
            'id_producto' => 'required|exists:Producto,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $producto = Producto::findOrFail($data['id_producto']);

        if ($producto->stock < $data['cantidad']) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuficiente para el producto: {$producto->nombre}."
            ], 400);
        }

        $subtotal = $producto->precio * $data['cantidad'];

        $pedido->items()->create([
            'id_producto' => $data['id_producto'],
            'cantidad' => $data['cantidad'],
            'precio' => $producto->precio,
            'subtotal' => $subtotal,
        ]);

        $pedido->increment('subtotal_productos', $subtotal);
        $pedido->increment('monto_total', $subtotal);
        $producto->decrement('stock', $data['cantidad']);

        return response()->json([
            'message' => 'Detalle agregado al pedido',
            'success' => true
        ], 200);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,confirmado,preparando,listo,en_camino,entregado,cancelado',
            'id_repartidor' => 'nullable|exists:Repartidor,id',
            'motivo' => 'nullable|string|max:500',
        ]);

        $pedido = Pedido::findOrFail($id);
        $estadoAnterior = $pedido->estado;
        $estadoNuevo = $request->estado;
        $usuario = $request->user();

        // Validar transiciones de estado según rol
        $transicionesValidas = [
            'pendiente' => ['confirmado', 'cancelado'],
            'confirmado' => ['preparando', 'cancelado'],
            'preparando' => ['listo', 'cancelado'],
            'listo' => ['en_camino', 'cancelado'],
            'en_camino' => ['entregado', 'cancelado'],
            'entregado' => [],
            'cancelado' => [],
        ];

        if (!in_array($estadoNuevo, $transicionesValidas[$estadoAnterior] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => "No se puede cambiar de '{$estadoAnterior}' a '{$estadoNuevo}'."
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Si cambia a en_camino, requiere repartidor
            if ($estadoNuevo === 'en_camino' && !$request->id_repartidor) {
                return response()->json([
                    'success' => false,
                    'message' => 'El repartidor es obligatorio para cambiar a estado en_camino.'
                ], 422);
            }

            // Actualizar pedido
            $updateData = ['estado' => $estadoNuevo];
            if ($request->id_repartidor) {
                $updateData['id_repartidor'] = $request->id_repartidor;
            }
            if ($estadoNuevo === 'entregado') {
                $updateData['estado_pago'] = 'completado';
                $updateData['monto_pagado'] = $pedido->monto_total ?? $pedido->total;
            }

            $pedido->update($updateData);

            // Registrar en historial
            HistorialPedido::create([
                'id_pedido' => $pedido->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'motivo' => $request->motivo,
                'usuario_id' => $usuario->id,
                'usuario_rol' => $this->resolveUserRole($usuario),
                'fecha_cambio' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado del pedido actualizado exitosamente.',
                'data' => [
                    'id' => $pedido->id,
                    'estado' => $pedido->estado,
                    'id_repartidor' => $pedido->id_repartidor,
                    'estado_pago' => $pedido->estado_pago,
                    'updated_at' => $pedido->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function registrarHistorial(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $data = $request->validate([
            'estado_anterior' => 'nullable|string',
            'estado_nuevo' => 'required|string',
            'motivo' => 'nullable|string|max:500',
        ]);

        $usuario = $request->user();

        // Registrar el evento en el historial
        $registro = HistorialPedido::create([
            'id_pedido' => $pedido->id,
            'estado_anterior' => $data['estado_anterior'],
            'estado_nuevo' => $data['estado_nuevo'],
            'motivo' => $data['motivo'],
            'usuario_id' => $usuario->id,
            'usuario_rol' => $this->resolveUserRole($usuario),
            'fecha_cambio' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Historial registrado exitosamente.',
            'data' => [
                'id' => $registro->id,
                'id_pedido' => $registro->id_pedido,
                'estado_anterior' => $registro->estado_anterior,
                'estado_nuevo' => $registro->estado_nuevo,
                'fecha_cambio' => $registro->fecha_cambio,
            ]
        ], 201);
    }

    protected function buildOrderPayload(Request $request, bool $full = false): array
    {
        $payload = [
            'id_comerciante' => $request->input('id_comerciante', $request->input('idComerciante')),
            'id_repartidor' => $request->input('id_repartidor', $request->input('idRepartidor')),
            'id_zona_envio' => $request->input('id_zona_envio', $request->input('idZonaEnvio')),
            'id_ubicacion_entrega' => $request->input('id_ubicacion_entrega', $request->input('idUbicacionEntrega')),
            'direccion_entrega' => $request->input('direccion_entrega', $request->input('direccionEntrega')),
            'latitud_entrega' => $request->input('latitud_entrega', $request->input('latitudEntrega')),
            'longitud_entrega' => $request->input('longitud_entrega', $request->input('longitudEntrega')),
            'subtotal_productos' => $request->input('subtotal_productos', $request->input('subtotalProductos')),
            'costo_envio' => $request->input('costo_envio', $request->input('costoEnvio')),
            'comisiones' => $request->input('comisiones'),
            'total' => $request->input('total'),
            'items' => $request->input('items'),
        ];

        if ($full) {
            $payload = array_merge($payload, [
                'tipo_pago' => $request->input('tipo_pago', $request->input('tipoPago')),
                'referencia_pago' => $request->input('referencia_pago', $request->input('referenciaPago')),
                'estado_pago' => $request->input('estado_pago', $request->input('estadoPago', 'pendiente')),
                'monto_pagado' => $request->input('monto_pagado', $request->input('montoPagado')),
            ]);
        }

        return $payload;
    }

    protected function calculateShippingCost($latitud = null, $longitud = null): float
    {
        if (is_null($latitud) || is_null($longitud)) {
            return 0.00;
        }

        // Placeholder: si necesitas calcular distancia real con Google Maps u otra API,
        // reemplaza esta implementación con la lógica de cálculo adecuada.
        return 0.00;
    }

    protected function resolveUserRole($user): string
    {
        if ($user->comerciante) {
            return 'comerciante';
        }

        if ($user->repartidor) {
            return 'repartidor';
        }

        if ($user->cliente) {
            return 'cliente';
        }

        return 'usuario';
    }
}
