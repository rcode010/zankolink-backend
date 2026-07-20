<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    public function generate(
        Model $model,
        string $disk = 'private',
        string $directory = 'qr-codes',
        int $size = 400
    ): string {
        $data = $this->buildQrData($model);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::H,
            'scale' => max(1, intdiv($size, 25)),
            'outputBase64' => false,
        ]);

        $qrCode = new QRCode($options);
        $pngData = $qrCode->render($data);

        $filename = sprintf(
            '%s-%s.png',
            strtolower(class_basename($model)),
            $model->getKey()
        );

        $path = $directory.'/'.$filename;

        Storage::disk($disk)->put($path, $pngData);

        return $path;
    }

    public function delete(string $path, string $disk = 'private'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    public function toDataUrl(Model $model, int $size = 200): string
    {
        $data = $this->buildQrData($model);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::H,
            'scale' => max(1, intdiv($size, 25)),
            'outputBase64' => false,
        ]);

        $qrCode = new QRCode($options);
        $pngData = $qrCode->render($data);

        return 'data:image/png;base64,'.base64_encode($pngData);
    }

    protected function buildQrData(Model $model): string
    {
        return $model->verification_url;
    }
}
