<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\API\PedidoController as ApiPedidoController;
use App\Http\Controllers\API\CalificacionController;
use App\Http\Controllers\API\ComercianteController;
use App\Http\Controllers\API\SeguimientoController as ApiSeguimientoController;
use App\Http\Controllers\API\TransaccionController;
use App\Http\Controllers\API\ShippingController;
use App\Http\Controllers\API\MerchantAuditController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\UploadController as UploadFileController;

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register']);

// Public helper route to serve uploaded files with CORS headers
Route::get('uploads/productos/{filename}', function ($filename) {
    $path = storage_path('uploads/productos/' . $filename);
    if (!file_exists($path)) {
        return response()->json(['message' => 'Not found'], 404);
    }

    $response = response()->file($path);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Content-Type', mime_content_type($path));

    return $response;
})->where('filename', '.*');

Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('catalogo', [ProductoController::class, 'catalogo']);
Route::get('comerciantes', [ComercianteController::class, 'index']);
Route::get('comerciantes/{id}', [ComercianteController::class, 'show']);
Route::get('comerciantes/{id}/audit_logs', [ComercianteController::class, 'getAuditLogs']);
Route::get('pedidos/{id}/seguimiento-gps', [ApiSeguimientoController::class, 'obtenerRutaSimulada']);
Route::get('calcular-envio', [ShippingController::class, 'calcular']);

// Financial audit (public read; write protected below)
Route::get('transacciones',         [TransaccionController::class, 'index']);
Route::get('transacciones/resumen', [TransaccionController::class, 'resumen']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('upload', [UploadFileController::class, 'store']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user-profile', [AuthController::class, 'profile']);
    Route::patch('user-profile', [AuthController::class, 'updateProfile']);
    Route::patch('users/{id}/upgrade', [AuthController::class, 'upgrade']);

    Route::patch('comerciantes/{id}', [ComercianteController::class, 'update']);
    Route::post('comerciantes/{id}/audit_logs', [ComercianteController::class, 'storeAuditLog']);

    Route::apiResource('productos', ProductoController::class);

    Route::get('pedidos', [PedidoController::class, 'index']);
    Route::post('pedidos', [ApiPedidoController::class, 'store']);
    Route::post('calificaciones', [CalificacionController::class, 'store']);
    Route::post('calificaciones/repartidor', [CalificacionController::class, 'calificarRepartidor']);
    Route::put('calificaciones/{id}', [CalificacionController::class, 'update']);
    Route::get('calificaciones/pedido', [CalificacionController::class, 'porPedido']);
    Route::get('calificaciones/producto', [CalificacionController::class, 'porProducto']);
    Route::get('pedidos/{id}', [PedidoController::class, 'show']);
    Route::put('pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado']);
    Route::post('pedidos/{id}/detalles', [PedidoController::class, 'agregarDetalle']);
    Route::post('pedidos/completo', [PedidoController::class, 'storeCompleto']);
    Route::post('pedidos/{id}/historial_estado', [PedidoController::class, 'registrarHistorial']);
    Route::post('seguimiento', [SeguimientoController::class, 'update']);

    // Merchant audit — financial summary from completed orders
    Route::get('merchant/audit', [MerchantAuditController::class, 'index']);

    // Financial transactions (admin / internal use)
    Route::post('transacciones', [TransaccionController::class, 'store']);
});

// Ejemplos de uso de middleware por rol (comentados para evitar duplicar rutas existentes)
// Descomenta y adapta a tus controladores/acciones cuando los implementes.
/*
Route::middleware('auth:sanctum')->group(function () {
    // ZONA CLIENTE: Solo usuarios con rol 'cliente'
    Route::middleware('checkRole:cliente')->group(function () {
        Route::get('pedidos/mis-compras', function () {
            return response()->json(['message' => 'Implementar historialCliente']);
        });
        Route::post('pedidos/crear', function () {
            return response()->json(['message' => 'Implementar crearPedido']);
        });
    });

    // ZONA COMERCIANTE: Solo usuarios con rol 'comerciante'
    Route::middleware('checkRole:comerciante')->group(function () {
        Route::get('productos/mis-productos', function () {
            return response()->json(['message' => 'Implementar indexComerciante']);
        });
        Route::post('productos/guardar', function () {
            return response()->json(['message' => 'Implementar store']);
        });
    });

    // ZONA REPARTIDOR: Solo usuarios con rol 'repartidor'
    Route::middleware('checkRole:repartidor')->group(function () {
        Route::get('entregas/pendientes', function () {
            return response()->json(['message' => 'Implementar entregas pendientes']);
        });
        Route::put('entregas/{id}/actualizar-estado', function () {
            return response()->json(['message' => 'Implementar updateEstado']);
        });
    });
});
*/

// =========================================================================
// RUTA DE PRUEBA DE ROLES (Mantener comentada como referencia de testing)
// =========================================================================
// Route::middleware(['auth:sanctum', 'checkRole:comerciante'])->get('/test-comerciante', function () {
//     return response()->json(['message' => '¡Éxito! Lograste burlar el middleware porque eres comerciante.']);
// });
