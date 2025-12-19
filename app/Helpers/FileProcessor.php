<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Helpers;

class FileProcessor
{
    /**
     * Procesa un archivo (nuevo o existente) y retorna su información
     *
     * @param UploadedFile|string|null $file Archivo a procesar
     * @param string $folder Carpeta de destino
     * @param string|null $oldFilePath Path del archivo anterior a eliminar
     * @param string $disk Disco de almacenamiento
     * @param bool $convertToWebp Si debe convertir imágenes a WebP
     * @return array{path: string, type: string}|null
     */
    public static function process(
        mixed $file,
        string $folder = 'uploads',
        ?string $oldFilePath = null,
        string $disk = 'public',
        bool $convertToWebp = true
    ): ?array {

        // 1. LIMPIAR LA RUTA ANTES DE BORRAR
        if ($oldFilePath) {
            $cleanPath = Helpers::removeAppUrl($oldFilePath);

            if (Storage::disk($disk)->exists($cleanPath)) {
                Storage::disk($disk)->delete($cleanPath);
            }
        }

        if ($file instanceof UploadedFile) {
            $type = self::getFileTypeFromMime($file->getMimeType());

            $path = match ($type) {
                'video' => $file->store($folder, $disk),
                'image' => $convertToWebp
                ? Helpers::saveWebpFile($file, $folder)
                : $file->store($folder, $disk),
                default => $file->store($folder, $disk)
            };

            return compact('path', 'type');
        }

        if (is_string($file)) {
            return [
                'path' => Helpers::removeAppUrl($file),
                'type' => self::getFileTypeFromExtension($file)
            ];
        }

        return null;
    }

    /**
     * Obtiene el tipo de archivo desde el MIME type
     */
    private static function getFileTypeFromMime(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'application/pdf') => 'pdf',
            default => 'other'
        };
    }

    /**
     * Obtiene el tipo de archivo desde la extensión
     */
    private static function getFileTypeFromExtension(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico']) => 'image',
            in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv', 'wmv']) => 'video',
            in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'aac']) => 'audio',
            $ext === 'pdf' => 'pdf',
            in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']) => 'document',
            default => 'other'
        };
    }

    /**
     * Elimina un archivo del storage
     */
    public static function delete(?string $filePath, string $disk = 'public'): bool
    {
        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            return false;
        }

        return Storage::disk($disk)->delete($filePath);
    }

    /**
     * Procesa múltiples archivos
     *
     * @param array $files
     * @param string $folder
     * @param array $oldFilePaths
     * @return array
     */
    public static function processMultiple(
        array $files,
        string $folder = 'uploads',
        array $oldFilePaths = [],
        string $disk = 'public',
        bool $convertToWebp = true
    ): array {
        $processed = [];

        foreach ($files as $index => $file) {
            $oldPath = $oldFilePaths[$index] ?? null;

            if ($result = self::process($file, $folder, $oldPath, $disk, $convertToWebp)) {
                $processed[] = $result;
            }
        }

        return $processed;
    }
}
