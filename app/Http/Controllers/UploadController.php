<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|max:10240',
            'tipo' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'modelo_id' => 'nullable|integer',
        ]);

        $file = $data['file'];
        $nombreOriginal = $file->getClientOriginalName();
        $nombreServidor = uniqid('archivo_', true) . '.' . $file->getClientOriginalExtension();
        $ruta = $file->storeAs('productos', $nombreServidor, 'public');

        $archivo = Archivo::create([
            'id_usuario' => $request->user()->id ?? null,
            'nombre_original' => $nombreOriginal,
            'nombre_servidor' => $nombreServidor,
            'ruta' => 'storage/' . $ruta,
            'tipo' => $data['tipo'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'modelo_id' => $data['modelo_id'] ?? null,
        ]);

        return response()->json(['data' => $archivo], 201);
    }
}
