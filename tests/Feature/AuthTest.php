<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'cliente',
            'nombre' => 'Juan Pérez',
            'telefono' => '70000000',
            'direccion' => 'Calle Falsa 123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user' => ['id', 'name', 'email']
                 ]);

        $this->assertDatabaseHas('Usuario', [
            'email' => 'juan@example.com'
        ]);
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'pedro@example.com',
            'password' => Hash::make('secreto123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pedro@example.com',
            'password' => 'secreto123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'maria@example.com',
            'password' => Hash::make('secreto123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'clave_erronea'
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user-profile');

        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => $user->email]);
    }
}
