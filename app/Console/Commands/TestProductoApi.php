<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestProductoApi extends Command
{
    protected $signature = 'test:producto-api';
    protected $description = 'Test POST /api/productos with base64 image';

    public function handle()
    {
        $this->info('🧪 Testing Producto API with Base64 Image...');

        // Obtener usuario comerciante por email
        $user = User::where('email', 'comerciante@mdrmarket.local')->first();
        if (!$user) {
            $this->error('❌ Comerciante user not found. Run: php artisan migrate:fresh --seed');
            return;
        }

        // Generar token
        $token = $user->createToken('test-token')->plainTextToken;
        $this->info('✅ Token generado: ' . substr($token, 0, 20) . '...');

        // Crear imagen de prueba en base64 (1x1 pixel PNG)
        $testImageBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

        $payload = [
            'id_categoria' => 1,
            'nombre' => 'Producto Test Base64 ' . now()->format('H:i:s'),
            'descripcion' => 'Producto creado con imagen en base64 desde CLI',
            'precio' => 15.99,
            'stock' => 5,
            'imagen' => $testImageBase64
        ];

        $this->info('📤 Enviando POST /api/productos...');

        try {
            $response = Http::withToken($token)
                ->post('http://mdrmarket_api.test/api/productos', $payload);

            $this->info('📊 HTTP Status: ' . $response->status());
            $this->info('📋 Respuesta:');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response->successful()) {
                $this->info('✅ ¡Producto creado exitosamente!');
            } else {
                $this->error('❌ Error en la respuesta: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->info('💡 Asegúrate de que Laragon está ejecutando el servidor en http://localhost:8000');
        }
    }
}
