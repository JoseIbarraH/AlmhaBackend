<?php

namespace App\Http\Requests\Dashboard\Design;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarouselBackgroundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carouselSetting' => ['required', 'boolean'],
            'imageVideoSetting' => ['required', 'boolean'],

            // Carousel URLs
            'carousel' => ['nullable', 'array'],
            'carousel.*.path' => [
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
                    }
                    // Si es string
                    elseif (!is_string($value)) {
                        $fail(__('validation.url_or_text_invalid', ['attribute' => $attribute]));
                    }
                },
            ],
            'carousel.*.title' => ['nullable', 'string'],
            'carousel.*.subtitle' => ['nullable', 'string'],

            // Image/Video URL
            'imageVideo' => ['nullable', 'array'],
            'imageVideo.path' => [
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
            'imageVideo.title' => ['nullable', 'string'],
            'imageVideo.subtitle' => ['nullable', 'string'],
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
        $maxSize = $isVideo ? 102400 : 10240; // 100MB para videos, 10MB para imágenes (en KB)

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

    public function messages(): array
    {
        return [
            // General
            'carousel.required' => __('validation.carousel_required'),
            'carousel.boolean' => __('validation.carousel_boolean'),

            'imageVideo.required' => __('validation.imageVideo_required'),
            'imageVideo.boolean' => __('validation.imageVideo_boolean'),

            // Carousel URLs
            'carouselUrls.array' => __('validation.carouselUrls_array'),
            'carouselUrls.*.title.string' => __('validation.carouselUrls_title_string'),
            'carouselUrls.*.subtitle.string' => __('validation.carouselUrls_subtitle_string'),

            // Image/Video URL
            'imageVideoUrl.required' => __('validation.imageVideoUrl_required'),
            'imageVideoUrl.string' => __('validation.imageVideoUrl_string'),
        ];
    }


}
