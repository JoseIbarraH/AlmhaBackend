<?php

namespace App\Http\Requests\Dashboard\Design;

use Illuminate\Foundation\Http\FormRequest;

class CarouselNavbarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carouselNavbar' => ['nullable', 'array'],
            'carouselNavbar.*.path' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (request()->hasFile($attribute)) {
                        $file = request()->file($attribute);

                        if (is_array($file)) {
                            foreach ($file as $f) {
                                $this->validateImageOrVideo($f, $attribute, $fail);
                            }
                        } else {
                            $this->validateImageOrVideo($file, $attribute, $fail);
                        }
                    } elseif (!is_string($value)) {
                        $fail(__('validation.url_or_text_invalid', ['attribute' => $attribute]));
                    }
                },
            ],
            'carouselNavbar.*.title' => ['nullable', 'string'],
            'carouselNavbar.*.subtitle' => ['nullable', 'string'],
        ];
    }

    private function validateImageOrVideo($file, $attribute, $fail)
    {
        // Validar que sea un archivo válido
        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            $fail(__('validation.file', ['attribute' => $attribute]));
            return;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // Extensiones y MIME types permitidos
        $allowedExtensions = [
            'image' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg'],
            'video' => ['mp4', 'mov', 'avi', 'mkv', 'webm']
        ];

        $allowedMimes = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            'video' => ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm']
        ];

        // Determinar si es imagen o video
        $isImage = in_array($extension, $allowedExtensions['image']) &&
            in_array($mimeType, $allowedMimes['image']);
        $isVideo = in_array($extension, $allowedExtensions['video']) &&
            in_array($mimeType, $allowedMimes['video']);

        // Validar que sea imagen o video válido
        if (!$isImage && !$isVideo) {
            $fail(__('validation.image_or_video_invalid'));
            return;
        }

        // Validar tamaño según tipo
        $maxSize = $isVideo ? 102400 : 15345; // 100MB para videos, 15MB para imágenes (en KB)

        if ($file->getSize() > $maxSize * 1024) {
            $fail(__('validation.file_too_large', [
                'max' => $maxSize / 1024 . 'MB'
            ]));
            return;
        }

        // Validar dimensiones solo para imágenes
        if ($isImage && $extension !== 'svg') {
            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];

                // Validar dimensiones mínimas
                if ($width < 200 || $height < 200) {
                    $fail(__('validation.image_dimensions_min'));
                    return;
                }

                // Validar dimensiones máximas
                if ($width > 5000 || $height > 5000) {
                    $fail(__('validation.image_dimensions_max'));
                    return;
                }
            }
        }
    }

}
