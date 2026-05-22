<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $action = new CreateNewUser;

        $action->create([
            'name' => 'Rodrigo Serafim',
            'email' => 'rdgo.serafim@gmail.com',
            'password' => '@belha22', // Substitua pela senha desejada
            'password_confirmation' => '@belha22', // Confirmação da senha
            'terms' => true,
        ]);
    }
}
