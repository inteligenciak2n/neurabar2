<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateServiceLocationAction;
use App\Actions\Settings\DeleteServiceLocationAction;
use App\Actions\Settings\GenerateQrTokenAction;
use App\Actions\Settings\UpdateServiceLocationAction;
use App\Enums\ServiceLocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreServiceLocationRequest;
use App\Http\Requests\Settings\UpdateServiceLocationRequest;
use App\Models\Settings\ServiceLocation;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceLocationController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/ServiceLocations', [
            'locations' => $venue
                                ->serviceLocations()
                                ->with('defaultAttendanceChannel:id,name')
                                ->get()
                                ->map(fn ($location) => [
                                    'id' => $location->id,
                                    'name' => $location->name,
                                    'type' => $location->type->value,
                                    'active' => $location->active,
                                    'default_attendance_channel' => $location->defaultAttendanceChannel,
                                    'qr_token' => $location->qr_token,
                                    'hub_url' => url('/g/'.$location->qr_token)
                                ]),
            'locationTypes' => array_column(ServiceLocationType::cases(), 'value'),
            'attendanceChannels' => $venue->attendanceChannels()->where('active', true)->get(['id', 'name']),
        ]);
    }

    public function store(StoreServiceLocationRequest $request, CreateServiceLocationAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'Service location created.');
    }

    public function update(UpdateServiceLocationRequest $request, ServiceLocation $location, UpdateServiceLocationAction $action): RedirectResponse
    {
        $action->execute($location, $request);

        return back()->with('success', 'Service location updated.');
    }

    public function destroy(ServiceLocation $location, DeleteServiceLocationAction $action): RedirectResponse
    {
        $action->execute($location);

        return back()->with('success', 'Service location deleted.');
    }

    public function generateQr(ServiceLocation $location, GenerateQrTokenAction $action): RedirectResponse
    {
        $action->execute($location);

        return back()->with('success', 'QR code generated.');
    }

    public function qrPdf(ServiceLocation $location): HttpResponse
    {
        abort_if($location->qr_token === null, 404, 'QR code not generated yet.');

        $venue = $location->venue()->withoutGlobalScopes()->first();

        $hubUrl = url('/g/'.$location->qr_token);

        $qrResult = (new Builder(
            writer: new PngWriter,
            data: $hubUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();

        $qrBase64 = 'data:image/png;base64,'.base64_encode($qrResult->getString());

        $logoBase64 = null;
        if ($venue->logo_url) {
            $logoContents = @file_get_contents($venue->logo_url);
            if ($logoContents !== false) {
                $mime = 'image/png';
                if (str_ends_with($venue->logo_url, '.jpg') || str_ends_with($venue->logo_url, '.jpeg')) {
                    $mime = 'image/jpeg';
                }
                $logoBase64 = 'data:'.$mime.';base64,'.base64_encode($logoContents);
            }
        }

        $pdf = Pdf::loadView('pdf.service-location-qr', [
            'venueName' => $venue->name,
            'locationName' => $location->name,
            'locationType' => $location->type->value,
            'qrBase64' => $qrBase64,
            'logoBase64' => $logoBase64,
            'hubUrl' => $hubUrl,
        ])->setPaper('a4', 'portrait');

        $filename = 'qr-'.str($location->name)->slug().'.pdf';

        return $pdf->stream($filename);
    }
}
