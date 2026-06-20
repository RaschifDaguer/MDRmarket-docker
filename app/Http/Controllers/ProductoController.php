<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $comercianteId = $request->user()->id;

        $productos = Producto::where('id_comerciante', $comercianteId)
            ->with(['categoria', 'imagenes'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos
        ], 200);
    }

    public function catalogo(Request $request)
    {
        // Mostrar sólo productos con stock disponible
        $query = Producto::with(['categoria', 'imagenes', 'comerciante'])
            ->withCount(['calificaciones as numero_resenas' => function ($q) {
                $q->whereNotNull('estrellas_producto');
            }])
            ->where('stock', '>', 0);

        // Soportar tanto 'categoria_id' como 'id_categoria' enviado por distintos clientes
        if ($request->has('categoria_id')) {
            $query->where('id_categoria', $request->query('categoria_id'));
        } elseif ($request->has('id_categoria')) {
            $query->where('id_categoria', $request->query('id_categoria'));
        }

        if ($request->has('comerciante_id')) {
            $query->where('id_comerciante', $request->query('comerciante_id'));
        }

        // Soportar parámetros de búsqueda en diferentes nombres: 'search' o 'buscar'
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%' . $request->query('search') . '%');
        } elseif ($request->has('buscar')) {
            $query->where('nombre', 'like', '%' . $request->query('buscar') . '%');
        }

        // Paginación configurable por el cliente (por defecto 12)
        $perPage = max(1, min((int) $request->query('perPage', 12), 100));

        $productos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'payload' => $productos
        ], 200);
    }

    public function store(Request $request)
    {
        $comercianteId = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'id_categoria'      => 'required|integer|exists:Categoria,id',
            'nombre'            => 'required|string|max:255',
            'descripcion'       => 'nullable|string',
            'precio'            => 'required|numeric|min:0',
            'stock'             => 'nullable|integer|min:0',
            'en_oferta'         => 'nullable|boolean',
            'precio_oferta'     => 'nullable|numeric|min:0',
            'foto'              => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,bmp,svg,tiff,ico,avif|max:204800',
            'imagen_producto'   => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,bmp,svg,tiff,ico,avif|max:204800',
            'imagen'            => 'nullable|string',
            'url_imagen'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();

        try {
            // 1. Creamos el producto base con los campos de oferta
            $producto = Producto::create([
                'id_comerciante' => $comercianteId,
                'id_categoria'   => $request->id_categoria,
                'nombre'         => $request->nombre,
                'descripcion'    => $request->descripcion,
                'precio'         => $request->precio,
                'stock'          => $request->get('stock', 0),
                'en_oferta'      => $request->get('en_oferta', 0),
                'precio_oferta'  => $request->get('en_oferta') ? $request->precio_oferta : null,
            ]);

            $urlPublica = null;

            // 2. Procesamos la imagen si viene en la petición como archivo, Base64 o URL
            if ($request->hasFile('imagen_producto')) {
                $file = $request->file('imagen_producto');
                $path = $file->store('productos', 'uploads');
                $urlPublica = Storage::disk('uploads')->url($path);
            } elseif ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $path = $file->store('productos', 'uploads');
                $urlPublica = Storage::disk('uploads')->url($path);
            } elseif ($request->filled('imagen')) {
                $imageData = $request->imagen;

                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $type = strtolower($type[1]);

                    if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $imageData = base64_decode($imageData);

                        if ($imageData !== false) {
                            $fileName = 'prod_' . uniqid() . '.' . ($type === 'jpeg' ? 'jpg' : $type);
                            $storagePath = 'productos/' . $fileName;
                            Storage::disk('uploads')->put($storagePath, $imageData);
                            $urlPublica = Storage::disk('uploads')->url($storagePath);
                        }
                    }
                }
            } elseif ($request->filled('url_imagen')) {
                $urlPublica = $request->url_imagen;
            }

            if ($urlPublica) {
                $productoImagen = ProductoImagen::create([
                    'id_producto' => $producto->id,
                    'url'         => $urlPublica,
                    'principal'   => true,
                ]);

                $producto->update(['id_imagen_principal' => $productoImagen->id]);
            }

            DB::commit();

            $productoCompleto = Producto::with(['categoria', 'imagenes', 'imagenPrincipal', 'comerciante'])
                ->find($producto->id);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente con su imagen',
                'data'    => $productoCompleto
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar producto', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $comercianteId = $request->user()->id;

        $producto = Producto::where('id', $id)
            ->where('id_comerciante', $comercianteId)
            ->with(['categoria', 'imagenes', 'comerciante'])
            ->first();

        if (! $producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado o no autorizado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $comercianteId = $request->user()->id;

        $producto = Producto::where('id', $id)
            ->where('id_comerciante', $comercianteId)
            ->first();

        if (! $producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado o no autorizado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_categoria' => 'nullable|integer|exists:Categoria,id',
            'nombre'       => 'nullable|string|max:255',
            'descripcion'  => 'nullable|string',
            'precio'       => 'nullable|numeric|min:0',
            'stock'        => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 400);
        }

        $producto->update($request->only(['id_categoria', 'nombre', 'descripcion', 'precio', 'stock']));

        $productoActualizado = Producto::with(['categoria', 'imagenes', 'imagenPrincipal', 'comerciante'])
            ->find($producto->id);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'data'    => $productoActualizado
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $comercianteId = $request->user()->id;

        $producto = Producto::where('id', $id)
            ->where('id_comerciante', $comercianteId)
            ->first();

        if (! $producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado o no autorizado'
            ], 404);
        }

        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ], 200);
    }
}
