<?php

namespace Database\Seeders;

use App\Models\Tenant\AffiliateCode;
use Illuminate\Database\Seeder;

class AffiliateCodesSeeder extends Seeder
{
    public function run(): void
    {
        AffiliateCode::firstOrCreate(
            ['code' => 'NEURABAR2026'],
            [
                'name' => 'NeuraBar Indicação',
                'email' => 'indicacoes@neurabar.com',
                'status' => 'active',
                'metadata' => [],
            ]
        );
    }
}
