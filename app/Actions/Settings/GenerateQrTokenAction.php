<?php

namespace App\Actions\Settings;

use App\Models\Settings\ServiceLocation;

class GenerateQrTokenAction
{
    public function execute(ServiceLocation $location): string
    {
        $payload = [
            'v' => $location->venue_id,
            'l' => $location->id,
            'c' => $location->default_attendance_channel_id,
        ];

        $token = rtrim(base64_encode(json_encode($payload)), '=');

        $location->update(['qr_token' => $token]);

        return $token;
    }
}
