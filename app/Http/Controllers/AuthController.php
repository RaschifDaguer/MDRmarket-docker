<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Comerciante;
use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:Usuario,email',
            'password' => 'required|string|min:8|confirmed',
            'rol' => 'required|string|in:cliente,comerciante,repartidor',
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:50',
            'direccion' => 'nullable|string',
            'tipo' => 'required_if:rol,repartidor|nullable|string|in:moto,bicicleta,auto,camion',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if ($data['rol'] === 'cliente') {
            Cliente::create([
                'id' => $user->id,
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
            ]);
        }

        if ($data['rol'] === 'comerciante') {
            Comerciante::create([
                'id' => $user->id,
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
            ]);
        }

        if ($data['rol'] === 'repartidor') {
            Repartidor::create([
                'id' => $user->id,
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'placa' => null,
                'tipo' => $data['tipo'] ?? null,
            ]);
        }

        $user = $user->load(['cliente', 'comerciante', 'repartidor']);
        $user->rol = $this->detectRole($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Las credenciales ingresadas son incorrectas.'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $user = $user->load(['cliente', 'comerciante', 'repartidor']);
        $user->rol = $this->detectRole($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada'], 200);
    }

    public function profile(Request $request)
    {
        return response()->json(['data' => $request->user()->load(['cliente', 'comerciante', 'repartidor'])], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        
        // Actualizar datos del usuario
        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }
        if (isset($data['email'])) {
            $user->update(['email' => $data['email']]);
        }
        
        // Actualizar datos del cliente/comerciante/repartidor
        if ($user->cliente) {
            $cliente = $user->cliente;
            if (isset($data['nombre'])) $cliente->nombre = $data['nombre'];
            if (isset($data['telefono'])) $cliente->telefono = $data['telefono'];
            if (isset($data['direccion'])) $cliente->direccion = $data['direccion'];
            $cliente->save();
        }
        
        if ($user->comerciante) {
            $comerciante = $user->comerciante;
            if (isset($data['nombre'])) $comerciante->nombre = $data['nombre'];
            if (isset($data['telefono'])) $comerciante->telefono = $data['telefono'];
            if (isset($data['direccion'])) $comerciante->direccion = $data['direccion'];
            $comerciante->save();
        }
        
        if ($user->repartidor) {
            $repartidor = $user->repartidor;
            if (isset($data['nombre'])) $repartidor->nombre = $data['nombre'];
            if (isset($data['telefono'])) $repartidor->telefono = $data['telefono'];
            if (isset($data['placa'])) $repartidor->placa = $data['placa'];
            if (isset($data['tipo'])) $repartidor->tipo = $data['tipo'];
            $repartidor->save();
        }
        
        return response()->json([
            'access_token' => $request->bearerToken(),
            'token_type' => 'Bearer',
            'user' => $user->load(['cliente', 'comerciante', 'repartidor'])
        ], 200);
    }

    public function upgrade(Request $request, int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Usuario no encontrado.'], 404);
        }

        if ($request->user()->id !== $id) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rol'       => 'required|string|in:cliente,comerciante,repartidor',
            'nombre'    => 'nullable|string|max:255',
            'telefono'  => 'nullable|string|max:50',
            'direccion' => 'nullable|string',
            'tipo'      => Rule::requiredIf(fn () => $request->rol === 'repartidor' && ! $user->repartidor)
                            ->sometimes()->in(['moto', 'bicicleta', 'auto', 'camion']),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data   = $validator->validated();
        $rol    = $data['rol'];
        $nombre = $data['nombre'] ?? $user->name;

        if ($rol === 'cliente' && ! $user->cliente) {
            Cliente::create([
                'id'        => $user->id,
                'nombre'    => $nombre,
                'email'     => $user->email,
                'telefono'  => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
            ]);
        }

        if ($rol === 'comerciante' && ! $user->comerciante) {
            Comerciante::create([
                'id'        => $user->id,
                'nombre'    => $nombre,
                'email'     => $user->email,
                'telefono'  => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
            ]);
        }

        if ($rol === 'repartidor' && ! $user->repartidor) {
            Repartidor::create([
                'id'       => $user->id,
                'nombre'   => $nombre,
                'email'    => $user->email,
                'telefono' => $data['telefono'] ?? null,
                'placa'    => null,
                'tipo'     => $data['tipo'] ?? 'moto',
            ]);
        }

        $user = $user->fresh()->load(['cliente', 'comerciante', 'repartidor']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Cuenta actualizada exitosamente.',
            'user'    => $user,
        ], 200);
    }

    protected function detectRole(User $user): string
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
