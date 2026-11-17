<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateServiceLocationRangeAction;
use App\Actions\Settings\DeleteServiceLocationAction;
use App\Actions\Settings\GenerateQrTokenAction;
use App\Actions\Settings\UpdateServiceLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreServiceLocationRangeRequest;
use App\Http\Requests\Settings\UpdateServiceLocationRequest;
use App\Models\Settings\ServiceLocation;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

class ServiceLocationController extends Controller
{
    public function storeRange(StoreServiceLocationRangeRequest $request, CreateServiceLocationRangeAction $action): RedirectResponse
    {
        $result = $action->execute(app('tenant'), $request);
        $payload = [
            ...$result,
            'source' => $request->validated('source'),
        ];

        $redirect = back()->with('location_create_result', $payload);

        if ($result['created'] > 0 && $result['skipped'] === 0) {
            return $redirect->with('success', 'Locations created successfully');
        }

        return $redirect;
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

    public function qrPdf(Request $request, ServiceLocation $location): HttpResponse
    {
        $hubUrl = $this->hubUrl($location);
        $qrBase64 = 'data:image/png;base64,'.base64_encode($this->qrPngBinary($location));
        $venue = $location->venue()->withoutGlobalScopes()->first();

        $logoBase64 = null;
        $logoContents = $venue->logoFileContents();
        if ($logoContents !== null) {
            $rawLogo = (string) $venue->getRawOriginal('logo_url');
            $mime = 'image/png';
            if (str_ends_with(strtolower($rawLogo), '.jpg') || str_ends_with(strtolower($rawLogo), '.jpeg')) {
                $mime = 'image/jpeg';
            } elseif (str_ends_with(strtolower($rawLogo), '.webp')) {
                $mime = 'image/webp';
            }
            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode($logoContents);
        }

        $pdf = Pdf::loadView('pdf.service-location-qr', [
            'venueName' => $venue->name,
            'locationName' => $location->name,
            'qrBase64' => $qrBase64,
            'logoBase64' => $logoBase64,
            'hubUrl' => $hubUrl,
        ])->setPaper('a4', 'portrait');

        $filename = 'qr-'.str($location->name)->slug().'.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function qrPng(Request $request, ServiceLocation $location): HttpResponse
    {
        $filename = 'qr-'.str($location->name)->slug().'.png';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($this->qrBinary($location, new PngWriter), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    public function qrSvg(Request $request, ServiceLocation $location): HttpResponse
    {
        $filename = 'qr-'.str($location->name)->slug().'.svg';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($this->qrBinary($location, new SvgWriter), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    private function hubUrl(ServiceLocation $location): string
    {
        abort_if($location->qr_token === null, 404, 'QR code not generated yet.');

        return url('/g/'.$location->qr_token);
    }

    private function qrPngBinary(ServiceLocation $location): string
    {
        return $this->qrBinary($location, new PngWriter);
    }

    private function qrBinary(ServiceLocation $location, WriterInterface $writer): string
    {
        return (new Builder(
            writer: $writer,
            data: $this->hubUrl($location),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build()->getString();
    }
}
