<?php

namespace App\Actions\Platform;

use App\Actions\Corporation\CreateVenueAction;
use App\Actions\Fortify\CreateNewUser;
use App\Jobs\Platform\SendWelcomeEmailJob;
use App\Models\Tenant\Corporation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCorporationAction
{
    public function __construct(
        private readonly CreateVenueAction $createVenueAction,
        private readonly CreateNewUser $createNewUser,
    ) {}

    public function execute(array $data): Corporation
    {
        return DB::transaction(function () use ($data) {
            $corporation = Corporation::create([
                'name' => $data['name'],
                'tax_id' => $data['tax_id'] ?? null,
                'email' => $data['email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'active' => true,
            ]);

            $this->createVenueAction->execute($corporation, [
                'name' => $data['name'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'timezone' => $data['timezone'] ?? 'America/Sao_Paulo',
            ]);

            $temporaryPassword = Str::random(12);

            $owner = $this->createNewUser->create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => $temporaryPassword,
                'password_confirmation' => $temporaryPassword,
                'terms' => true,
            ]);

            SendWelcomeEmailJob::dispatch($corporation, $owner, $temporaryPassword);

            return $corporation;
        });
    }
}
