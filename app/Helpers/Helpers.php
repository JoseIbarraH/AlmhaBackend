<?php

namespace App\Helpers;

use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Helpers
{

    /* public static function saveWebpFile(UploadedFile $file, string $folder): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file)->toWebp(80);
        $filename = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
        $path = trim($folder, '/') . '/' . $filename;
        Storage::disk('public')->put($path, $image);
        return $path;
    } */

    public static function saveWebpFile(UploadedFile $file, string $folder): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file)->toWebp(80);
        $randomName = Str::random(10);
        $filename = $randomName . '.webp';
        $path = trim($folder, '/') . '/' . $filename;
        Storage::disk('public')->put($path, $image);

        return $path;
    }

    public static function saveWebpFileWaterMaker(UploadedFile $file, string $folder): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);
        $watermark = $manager->read(storage_path('app/public/logo.png'));
        $watermark->scale(width: intval($image->width() * 0.25));
        $image->place($watermark, 'bottom-right', 20, 20);
        $webp = $image->toWebp(80);
        $filename = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
        $path = trim($folder, '/') . '/' . $filename;
        Storage::disk('public')->put($path, (string) $webp);

        return $path;
    }

    public static function translateBatch(array $texts, string $source = 'es', string $target = 'en', string $format = 'html'): array
    {
        if (empty($texts)) {
            return [];
        }

        // Asegura que la URL tenga /translate al final
        $baseUrl = rtrim(env('LIBRETRANSLATE_URL'), '/');
        $url = $baseUrl . '/translate';

        $translations = [];

        foreach ($texts as $text) {
            if (empty($text)) {
                $translations[] = '';
                continue;
            }

            $response = Http::asJson()->post($url, [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => $format,
            ]);

            if ($response->failed()) {
                throw new \Exception("LibreTranslate error: " . $response->body());
            }

            $data = $response->json();
            $translations[] = $data['translatedText'] ?? '';
        }

        return $translations;
    }



}
