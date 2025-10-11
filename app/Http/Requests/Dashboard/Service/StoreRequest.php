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
            'service_image' => 'nullable|image|max:5120',

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
            'sample_images.technique' => 'nullable|image|max:5120',
            'sample_images.recovery' => 'nullable|image|max:5120',
            'sample_images.postoperative_care' => 'nullable|image|max:5120',

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
                            $fail("El archivo en {$attribute} no es válido.");
                            return;
                        }

                        // Validar tipo y tamaño
                        $ext = strtolower($value->getClientOriginalExtension());
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                            $fail("El formato del archivo en {$attribute} no es válido.");
                        }

                        if ($value->getSize() > 102400 * 1024) { // 100 MB
                            $fail("La imagen en {$attribute} supera el límite de 100MB.");
                        }

                        return;
                    }

                    // ❌ Si no es ni string ni archivo
                    $fail("El campo {$attribute} debe ser una imagen o una cadena de texto (URL).");
                },
            ],
        ];
    }
}
