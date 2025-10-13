<?php

namespace App\Http\Requests\Dashboard\Service;

use Illuminate\Foundation\Http\FormRequest;
use \Illuminate\Http\UploadedFile;


class StoreRequest extends FormRequest
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
    public function rules()
    {
        return [
            'status' => 'required|string|in:active,inactive',
            'service_image' => 'nullable|image|max:15360',

            'title' => 'required|string',
            'description' => 'required|string',

            // Fases quirúrgicas
            'surgery_phases' => 'array|nullable',
            'surgery_phases.*.recovery_time' => 'required_with:surgery_phases.*|string',
            'surgery_phases.*.preoperative_recommendations' => 'required_with:surgery_phases.*|string',
            'surgery_phases.*.postoperative_recommendations' => 'required_with:surgery_phases.*|string',

            // Preguntas frecuentes
            'frequently_asked_questions' => 'array|nullable',
            'frequently_asked_questions.*.question' => 'required_with:frequently_asked_questions.*.answer|string',
            'frequently_asked_questions.*.answer' => 'required_with:frequently_asked_questions.*.question|string',

            // Imágenes de muestra

            'sample_images' => 'array|nullable',
            'sample_images.technique' => 'nullable|image|max:15360',
            'sample_images.recovery' => 'nullable|image|max:15360',
            'sample_images.postoperative_care' => 'nullable|image|max:15360',

            // Galería de resultados
            'results_gallery' => 'array|nullable',
            'results_gallery.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value)) {
                        return;
                    }

                    if ($value instanceof UploadedFile) {
                        if (!$value->isValid()) {
                            $fail(__('validation.file_invalid', ['attribute' => $attribute]));
                            return;
                        }

                        // Validar tipo y tamaño
                        $ext = strtolower($value->getClientOriginalExtension());
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                            $fail(__('validation.file_format_invalid', ['attribute' => $attribute]));
                        }

                        if ($value->getSize() > 15360 * 1024) { // 10 MB
                            $fail(__('validation.file_too_large_10mb', ['attribute' => $attribute]));
                        }

                        return;
                    }

                    $fail(__('validation.file_must_be_image_or_string', ['attribute' => $attribute]));
                },
            ],
        ];
    }
}
