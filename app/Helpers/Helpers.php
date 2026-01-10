<?php

namespace App\Helpers;

use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;


class Helpers
{
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

    public static function saveVideoFile(UploadedFile $file, string $path): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($path, $filename, 'public');
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

    public static function removeAppUrl(string $url): string
    {
        $appUrl = config('app.url');

        if (str_starts_with($url, "$appUrl/storage")) {
            return ltrim(str_replace("$appUrl/storage", '', $url), '/');
        }

        return $url;
    }

    public static function generateUniqueSlug(string $modelClass, string $title, string $slugColumn = 'slug'): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while ($modelClass::where($slugColumn, $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    public static function generateUniqueCode(int $length = 10): string
    {
        do {
            $code = Str::lower(Str::random($length)); // alfanumérico
        } while (\DB::table('blog_categories')->where('code', $code)->exists());

        return $code;
    }

}
