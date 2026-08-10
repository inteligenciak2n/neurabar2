<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\CreateNewUserPlatform;
use App\Enums\ProfileEnum;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        // adiciona defaults da aplicação, como planos, estações, status, etc
        $this->call([
            PlanCatalogsSeeder::class,
            ModuleCatalogsSeeder::class,
            ModuleUsageTiersSeeder::class,
            AffiliateCodesSeeder::class,
            SupportDatabaseSeeder::class,
        ]);

        $action = new CreateNewUserPlatform;
        $action->create([
            'name' => 'Rodrigo Admin',
            'email' => 'rdgo.serafim@gmail.com',
            'password' => '@belha22',
            'password_confirmation' => '@belha22',
            'terms' => true,
            'profile' => ProfileEnum::SuperAdmin->value,
            'active' => true,
        ]);

        if(! app()->environment('production')) {
            $action = new CreateNewUser;
            $action->create([
                'name' => 'Jose Owner',
                'email' => 'jose.owner@test.com',
                'password' => '@belha22',
                'password_confirmation' => '@belha22',
                'terms' => true,
                'profile' => ProfileEnum::Client->value,
                'active' => true,
            ]);
        }
            
    }
}
