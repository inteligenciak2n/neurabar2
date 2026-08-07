<?php

namespace App\Models\Tenant;

use App\Enums\AffiliateCodeStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateCode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'code', 'name', 'email', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AffiliateCodeStatus::class,
            'metadata' => 'array',
        ];
    }
}
