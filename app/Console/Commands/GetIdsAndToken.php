<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Categoria;

class GetIdsAndToken extends Command
{
    protected $signature = 'get:ids-token';
    protected $description = 'Print comerciante id, categorias and create a test token (plaintext)';

    public function handle()
    {
        $user = User::where('email', 'comerciante@mdrmarket.local')->first();
        if (! $user) {
            $this->error('Comerciante user not found.');
            return 1;
        }

        $this->info('USER_ID: ' . $user->id);

        $cats = Categoria::all()->pluck('id', 'nombre');
        foreach ($cats as $nombre => $id) {
            $this->info('CAT: ' . $id . ' => ' . $nombre);
        }

        $token = $user->createToken('test-token')->plainTextToken;
        $this->info('TOKEN: ' . $token);

        return 0;
    }
}
