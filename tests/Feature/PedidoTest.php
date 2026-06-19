<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Comerciante;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_pedidos_debe_rechazar_usuarios_no_autenticados()
    {
        // 1. Sin credenciales, cualquier petición a /api/pedidos debe ser rechazada
        $response = $this->postJson('/api/pedidos', [
            'total' => 150.00,
            'productos' => []
        ]);

        // Debe rebotar con un error 401 Unauthorized
        $response->assertStatus(401);
    }

    public function test_un_usuario_autenticado_puede_crear_un_pedido_exitosamente()
    {
        // 1. Simulamos un usuario cliente en el sistema
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['id' => $user->id]);

        // 2. Simulamos un comerciante y repartidor con stock en Laragon
        $comerciante = Comerciante::factory()->create();
        $repartidor = Repartidor::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10, 'precio' => 20.00]);

        // 3. Autenticamos al usuario mediante Sanctum para la petición
        Sanctum::actingAs($user);

        // 4. Disparamos la petición POST con la estructura exacta que valida tu controlador
        $response = $this->postJson('/api/pedidos', [
            'id_comerciante' => $comerciante->id,
            'id_repartidor' => $repartidor->id,
            'id_zona_envio' => 1,
            'id_ubicacion_entrega' => null,
            'subtotal_productos' => 40.00,
            'costo_envio' => 5.00,
            'items' => [
                [
                    'id_producto' => $producto->id,
                    'cantidad' => 2,
                    'precio' => 20.00
                ]
            ]
        ]);

        // 5. Asertamos que el backend responda con éxito (201 Created)
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Pedido generado exitosamente.'
                 ]);

        // 6. Verificamos que el stock en la base de datos realmente haya bajado a 8
        $this->assertDatabaseHas('Producto', [
            'id' => $producto->id,
            'stock' => 8
        ]);
    }

    public function test_no_se_puede_comprar_mas_stock_del_disponible()
    {
        // 1. Preparamos usuario y datos
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['id' => $user->id]);
        $comerciante = Comerciante::factory()->create();
        $repartidor = Repartidor::factory()->create();

        // Solo tenemos 3 unidades de stock
        $producto = Producto::factory()->create(['stock' => 3, 'precio' => 10.00]);

        Sanctum::actingAs($user);

        // 2. Intentamos comprar 5 unidades (más de las disponibles)
        $response = $this->postJson('/api/pedidos', [
            'id_comerciante' => $comerciante->id,
            'id_repartidor' => $repartidor->id,
            'id_zona_envio' => 1,
            'id_ubicacion_entrega' => null,
            'subtotal_productos' => 50.00,
            'costo_envio' => 5.00,
            'items' => [
                [
                    'id_producto' => $producto->id,
                    'cantidad' => 5,
                    'precio' => 10.00
                ]
            ]
        ]);

        // 3. Debe rechazar con 400 Bad Request
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => "Stock insuficiente para el producto: {$producto->nombre}. Quedan disponibles: 3 unidades."
                 ]);

        // 4. El stock no debe haber cambiado
        $this->assertDatabaseHas('Producto', [
            'id' => $producto->id,
            'stock' => 3
        ]);
    }
}

