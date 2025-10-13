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
            'carousel' => ['required', 'boolean'],
            'imageVideo' => ['required', 'boolean'],

            // Carousel URLs
            'carouselUrls' => ['nullable', 'array'],
            'carouselUrls.*.url' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (request()->hasFile($attribute)) {
                        $file = request()->file($attribute);

                        if (is_array($file)) {
                            foreach ($file as $f) {
                                $this->validateFile($f, $attribute, $fail);
                            }
                        } else {
                            $this->validateFile($file, $attribute, $fail);
                        }
                    }
                    // Si es string
                    elseif (!is_string($value)) {
                        $fail(__('validation.url_or_text_invalid', ['attribute' => $attribute]));
                    }
                },
            ],
            'carouselUrls.*.title' => ['nullable', 'string'],
            'carouselUrls.*.subtitle' => ['nullable', 'string'],

            // Image/Video URL
            'imageVideoUrl' => ['nullable', 'array'],
            'imageVideoUrl.*.url' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (request()->hasFile($attribute)) {
                        $file = request()->file($attribute);

                        if (is_array($file)) {
                            foreach ($file as $f) {
                                $this->validateFile($f, $attribute, $fail);
                            }
                        } else {
                            $this->validateFile($file, $attribute, $fail);
                        }
                    } elseif (!is_string($value)) {
                        $fail(__('validation.url_or_text_invalid', ['attribute' => $attribute]));
                    }
                },
            ],
            'imageVideoUrl.*.title' => ['nullable', 'string'],
            'imageVideoUrl.*.subtitle' => ['nullable', 'string'],
        ];
    }

    private function validateFile($file, $attribute, $fail)
    {
        if (!in_array($file->getClientOriginalExtension(), ['jpeg', 'jpg', 'png', 'gif', 'svg'])) {
            $fail(__('validation.image_or_video_invalid'));
        }
        if ($file->getSize() > 102400 * 1024) {
            $fail(__('validation.file_too_large'));
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
