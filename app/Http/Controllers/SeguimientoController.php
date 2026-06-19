<?php

namespace App\Http\Controllers;

use App\Models\Seguimiento;
use Illuminate\Http\Request;

class SeguimientoController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'id_repartidor' => 'required|integer',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'estado' => 'nullable|string',
        ]);

        if ($request->user()->repartidor && $request->user()->repartidor->id !== $data['id_repartidor']) {
            return response()->json(['message' => 'No autorizado para actualizar este repartidor'], 403);
        }

        $seguimiento = Seguimiento::create($data);

        return response()->json(['data' => $seguimiento], 201);
    }

    public function obtenerRutaSimulada($id_pedido)
    {
        $puntosRuta = [
            ['lat' => -17.7830, 'lng' => -63.1820],
            ['lat' => -17.7865, 'lng' => -63.1780],
            ['lat' => -17.7900, 'lng' => -63.1720],
            ['lat' => -17.7945, 'lng' => -63.1654],
        ];

        $indiceActual = (int) (time() / 10) % count($puntosRuta);
        $repartidorPosicion = $puntosRuta[$indiceActual];

        return response()->json([
            'status' => 'success',
            'id_pedido' => $id_pedido,
            'comercio_ubicacion' => ['lat' => -17.7830, 'lng' => -63.1820],
            'cliente_ubicacion' => ['lat' => -17.7945, 'lng' => -63.1654],
            'repartidor_ubicacion' => $repartidorPosicion,
            'estado_entrega' => $indiceActual === count($puntosRuta) - 1 ? 'Entregado' : 'En Camino',
        ]);
    }
}
