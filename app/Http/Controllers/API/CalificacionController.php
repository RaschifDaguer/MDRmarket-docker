<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalificacionController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /api/calificaciones
    // Crea o actualiza (upsert) la reseña del cliente para un producto.
    // Un cliente solo puede tener UNA reseña por producto; si ya existe, se edita.
    // -------------------------------------------------------------------------
    public function store(Request $request)
    {
        Log::debug('Calificaciones payload', ['all' => $request->all()]);

        // Normalizar alias de rating que puede enviar el frontend
        if ($request->filled('rating') && ! $request->filled('estrellas_producto')) {
            $request->merge(['estrellas_producto' => $request->input('rating')]);
        }
        if ($request->filled('rating_repartidor') && ! $request->filled('estrellas_repartidor')) {
            $request->merge(['estrellas_repartidor' => $request->input('rating_repartidor')]);
        }

        // Convertir 0 → null: el frontend envía 0 cuando no califica ese campo
        if ((int) $request->input('estrellas_producto', -1) === 0) {
            $request->merge(['estrellas_producto' => null]);
        }
        if ((int) $request->input('estrellas_repartidor', -1) === 0) {
            $request->merge(['estrellas_repartidor' => null]);
        }

        $rules = [
            'id_producto'         => 'required|integer|exists:Producto,id',
            'estrellas_producto'  => 'nullable|integer|between:1,5',
            'estrellas_repartidor'=> 'nullable|integer|between:1,5',
            'comentario'          => 'nullable|string|max:1000',
        ];

        if ($request->filled('id_pedido')) {
            $rules['id_pedido'] = 'integer|exists:Pedido,id';
        } else {
            $rules['id_pedido'] = 'nullable';
        }

        $validated = $request->validate($rules);

        if (empty($validated['estrellas_producto']) && empty($validated['estrellas_repartidor'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Debes proporcionar al menos una calificación (producto o repartidor).',
            ], 422);
        }

        $user    = $request->user();
        $cliente = $user->cliente;

        if (! $cliente) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El usuario autenticado no es cliente.',
            ], 403);
        }

        $idProducto = (int) $validated['id_producto'];
        $idPedido   = isset($validated['id_pedido']) ? (int) $validated['id_pedido'] : null;

        // --- UPSERT: un cliente solo puede calificar un producto una vez ----------
        $existente = DB::table('calificaciones')
            ->where('id_cliente', $cliente->id)
            ->where('id_producto', $idProducto)
            ->first();

        $datos = [
            'comentario'  => $validated['comentario'] ?? null,
            'updated_at'  => now(),
        ];

        if (! empty($validated['estrellas_producto'])) {
            $datos['estrellas_producto'] = $validated['estrellas_producto'];
        }
        if (! empty($validated['estrellas_repartidor'])) {
            $datos['estrellas_repartidor'] = $validated['estrellas_repartidor'];
        }

        if ($existente) {
            DB::table('calificaciones')->where('id', $existente->id)->update($datos);
            $mensaje = 'Reseña actualizada correctamente.';
        } else {
            $datos = array_merge($datos, [
                'id_cliente'  => $cliente->id,
                'id_producto' => $idProducto,
                'created_at'  => now(),
            ]);
            if ($idPedido !== null) {
                $datos['id_pedido'] = $idPedido;
            }
            DB::table('calificaciones')->insert($datos);
            $mensaje = 'Reseña registrada correctamente.';
        }

        // --- Recalcular rating del producto --------------------------------------
        $ratingProducto = null;
        $ratingGeneral  = null;

        if (! empty($validated['estrellas_producto'])) {
            $ratingProducto = DB::table('calificaciones')
                ->where('id_producto', $idProducto)
                ->whereNotNull('estrellas_producto')
                ->avg('estrellas_producto');

            $ratingGeneral = DB::table('calificaciones')
                ->where('id_producto', $idProducto)
                ->whereNotNull('estrellas_producto')
                ->avg('estrellas_producto');

            DB::table('Producto')->where('id', $idProducto)->update([
                'rating_producto' => round((float) $ratingProducto, 1),
                'rating_general'  => round((float) $ratingGeneral, 1),
            ]);
        }

        // --- Recalcular rating del repartidor (si se calificó) -------------------
        if (! empty($validated['estrellas_repartidor']) && $idPedido !== null) {
            $this->recalcularRatingRepartidor($idPedido, (int) $validated['estrellas_repartidor']);
        }

        return response()->json([
            'status'          => 'success',
            'message'         => $mensaje,
            'rating_producto' => $ratingProducto !== null ? round((float) $ratingProducto, 1) : null,
            'rating_general'  => $ratingGeneral  !== null ? round((float) $ratingGeneral, 1)  : null,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // PUT /api/calificaciones/{id}
    // Edita una reseña existente (solo el autor puede editarla).
    // -------------------------------------------------------------------------
    public function update(Request $request, $id)
    {
        if ($request->filled('rating') && ! $request->filled('estrellas_producto')) {
            $request->merge(['estrellas_producto' => $request->input('rating')]);
        }
        if ($request->filled('rating_repartidor') && ! $request->filled('estrellas_repartidor')) {
            $request->merge(['estrellas_repartidor' => $request->input('rating_repartidor')]);
        }

        $validated = $request->validate([
            'estrellas_producto'   => 'sometimes|nullable|integer|between:1,5',
            'estrellas_repartidor' => 'sometimes|nullable|integer|between:1,5',
            'comentario'           => 'nullable|string|max:1000',
        ]);

        $user    = $request->user();
        $cliente = $user->cliente;

        if (! $cliente) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
        }

        $calificacion = DB::table('calificaciones')->where('id', $id)->first();
        if (! $calificacion) {
            return response()->json(['status' => 'error', 'message' => 'Calificación no encontrada.'], 404);
        }
        if ($calificacion->id_cliente != $cliente->id) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado para editar esta calificación.'], 403);
        }

        $update = ['updated_at' => now()];
        if (array_key_exists('estrellas_producto', $validated))   $update['estrellas_producto']   = $validated['estrellas_producto'];
        if (array_key_exists('estrellas_repartidor', $validated)) $update['estrellas_repartidor'] = $validated['estrellas_repartidor'];
        if (array_key_exists('comentario', $validated))           $update['comentario']           = $validated['comentario'];

        DB::table('calificaciones')->where('id', $id)->update($update);

        if ($calificacion->id_producto) {
            $avg = DB::table('calificaciones')
                ->where('id_producto', $calificacion->id_producto)
                ->whereNotNull('estrellas_producto')
                ->avg('estrellas_producto');

            DB::table('Producto')->where('id', $calificacion->id_producto)->update([
                'rating_producto' => $avg !== null ? round((float) $avg, 1) : null,
                'rating_general'  => $avg !== null ? round((float) $avg, 1) : null,
            ]);
        }

        if (! empty($validated['estrellas_repartidor']) && $calificacion->id_pedido) {
            $this->recalcularRatingRepartidor($calificacion->id_pedido, (int) $validated['estrellas_repartidor']);
        }

        return response()->json(['status' => 'success', 'message' => 'Calificación actualizada correctamente.'], 200);
    }

    // -------------------------------------------------------------------------
    // POST /api/calificaciones/repartidor
    // Califica exclusivamente al repartidor después de que el pedido fue entregado.
    // -------------------------------------------------------------------------
    public function calificarRepartidor(Request $request)
    {
        $validated = $request->validate([
            'id_pedido'            => 'required|integer|exists:Pedido,id',
            'estrellas_repartidor' => 'required|integer|between:1,5',
            'comentario'           => 'nullable|string|max:1000',
        ]);

        $user    = $request->user();
        $cliente = $user->cliente;

        if (! $cliente) {
            return response()->json(['status' => 'error', 'message' => 'Solo los clientes pueden calificar.'], 403);
        }

        $pedido = DB::table('Pedido')->where('id', $validated['id_pedido'])->first();

        if (! $pedido) {
            return response()->json(['status' => 'error', 'message' => 'Pedido no encontrado.'], 404);
        }

        if ($pedido->id_cliente != $cliente->id) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
        }

        // Si el pedido aún no está marcado como entregado, lo marcamos ahora.
        // (El cliente llegó a calificar, lo que implica que la entrega se completó.)
        if (strtolower($pedido->estado ?? '') !== 'entregado') {
            DB::table('Pedido')->where('id', $validated['id_pedido'])->update([
                'estado'     => 'entregado',
                'updated_at' => now(),
            ]);
        }

        // Upsert: un cliente solo puede calificar al repartidor de un pedido una vez
        $existente = DB::table('calificaciones')
            ->where('id_pedido', $validated['id_pedido'])
            ->where('id_cliente', $cliente->id)
            ->whereNull('id_producto')
            ->first();

        $datos = [
            'estrellas_repartidor' => $validated['estrellas_repartidor'],
            'comentario'           => $validated['comentario'] ?? null,
            'updated_at'           => now(),
        ];

        if ($existente) {
            DB::table('calificaciones')->where('id', $existente->id)->update($datos);
            $mensaje = 'Calificación del repartidor actualizada.';
        } else {
            DB::table('calificaciones')->insert(array_merge($datos, [
                'id_pedido'  => $validated['id_pedido'],
                'id_cliente' => $cliente->id,
                'created_at' => now(),
            ]));
            $mensaje = 'Calificación del repartidor registrada.';
        }

        $this->recalcularRatingRepartidor($validated['id_pedido'], $validated['estrellas_repartidor']);

        return response()->json(['status' => 'success', 'message' => $mensaje], 200);
    }

    // -------------------------------------------------------------------------
    // GET /api/calificaciones/pedido?id_pedido=X
    // -------------------------------------------------------------------------
    public function porPedido(Request $request)
    {
        $validated = $request->validate(['id_pedido' => 'required|exists:Pedido,id']);

        $calificaciones = DB::table('calificaciones as c')
            ->leftJoin('Producto as p', 'c.id_producto', '=', 'p.id')
            ->where('c.id_pedido', $validated['id_pedido'])
            ->select('c.*', 'p.nombre as producto_nombre')
            ->get();

        return response()->json(['status' => 'success', 'data' => $calificaciones], 200);
    }

    // -------------------------------------------------------------------------
    // GET /api/calificaciones/producto?id_producto=X
    // -------------------------------------------------------------------------
    public function porProducto(Request $request)
    {
        $validated = $request->validate(['id_producto' => 'required|exists:Producto,id']);

        $calificaciones = DB::table('calificaciones')
            ->where('id_producto', $validated['id_producto'])
            ->orderByDesc('created_at')
            ->get();

        $ratingPromedio = DB::table('calificaciones')
            ->where('id_producto', $validated['id_producto'])
            ->whereNotNull('estrellas_producto')
            ->avg('estrellas_producto');

        return response()->json([
            'status'          => 'success',
            'rating_promedio' => $ratingPromedio !== null ? round((float) $ratingPromedio, 1) : null,
            'total'           => $calificaciones->count(),
            'data'            => $calificaciones,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Helper: recalcular promedio del repartidor del pedido
    // -------------------------------------------------------------------------
    private function recalcularRatingRepartidor(int $idPedido, int $nuevaEstrella): void
    {
        $pedido = DB::table('Pedido')->where('id', $idPedido)->first();
        if (! $pedido || ! $pedido->id_repartidor) {
            return;
        }

        $promedio = DB::table('calificaciones as c')
            ->join('Pedido as p', 'c.id_pedido', '=', 'p.id')
            ->where('p.id_repartidor', $pedido->id_repartidor)
            ->whereNotNull('c.estrellas_repartidor')
            ->avg('c.estrellas_repartidor');

        $total = DB::table('calificaciones as c')
            ->join('Pedido as p', 'c.id_pedido', '=', 'p.id')
            ->where('p.id_repartidor', $pedido->id_repartidor)
            ->whereNotNull('c.estrellas_repartidor')
            ->count();

        DB::table('Repartidor')->where('id', $pedido->id_repartidor)->update([
            'rating_promedio'      => round((float) $promedio, 1),
            'total_calificaciones' => $total,
        ]);
    }
}
