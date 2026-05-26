<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        //adiciona defaults da aplicação, como planos, estações, status, etc
        $this->call([
            PlanCatalogsSeeder::class,
        ]);

        $action = new CreateNewUser();
        $action->create([
            'name' => 'Rodrigo Admin',
            'email' => 'rdgo.serafim@gmail.com',
            'password' => '@belha22',
            'password_confirmation' => '@belha22',
            'terms' => true,
            'role' => 'super_admin',
            'active' => true,
        ]);

        $action->create([
            'name' => 'Jose Owner',
            'email' => 'jose.owner@test.com',
            'password' => '@belha22',
            'password_confirmation' => '@belha22',
            'terms' => true,
            'role' => 'owner',
            'active' => true,
        ]);
    }
}
