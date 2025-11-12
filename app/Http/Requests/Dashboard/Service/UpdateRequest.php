<?php

namespace App\Http\Requests\Dashboard\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        \Log::info('Datos que llegaron al request:', $this->all());
        return [
            'status' => 'required|string|in:active,inactive',
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image'),
                    ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:15360']
                ),
                Rule::when(
                    is_string($this->input('image')),
                    ['string']
                ),
            ],

            'title' => 'required|string',
            'slug' => 'nullable',
            'description' => 'required|string',

            // Fases quirúrgicas
            'surgery_phases' => 'nullable|array',
            'surgery_phases.recovery_time' => 'nullable|array',
            'surgery_phases.recovery_time.*' => 'string',

            'surgery_phases.preoperative_recommendations' => 'nullable|array',
            'surgery_phases.preoperative_recommendations.*' => 'string',

            'surgery_phases.postoperative_recommendations' => 'nullable|array',
            'surgery_phases.postoperative_recommendations.*' => 'string',

            // Preguntas frecuentes
            'frequently_asked_questions' => 'array|nullable',
            'frequently_asked_questions.*.question' => 'required_with:frequently_asked_questions.*.answer|string',
            'frequently_asked_questions.*.answer' => 'required_with:frequently_asked_questions.*.question|string',

            // Imágenes de muestra

            'sample_images' => 'array|nullable',

            'sample_images.technique' => [
                'nullable',
                Rule::when(
                    $this->hasFile('sample_images.technique'),
                    ['image', 'max:15360']
                ),
                Rule::when(
                    is_string(data_get($this->input('sample_images'), 'technique')),
                    ['string']
                ),
            ],

            'sample_images.recovery' => [
                'nullable',
                Rule::when(
                    $this->hasFile('sample_images.recovery'),
                    ['image', 'max:15360']
                ),
                Rule::when(
                    is_string(data_get($this->input('sample_images'), 'recovery')),
                    ['string']
                ),
            ],

            'sample_images.postoperative_care' => [
                'nullable',
                Rule::when(
                    $this->hasFile('sample_images.postoperative_care'),
                    ['image', 'max:15360']
                ),
                Rule::when(
                    is_string(data_get($this->input('sample_images'), 'postoperative_care')),
                    ['string']
                ),
            ],

            // Galería de resultados
            'results_gallery' => 'array|nullable',
            'results_gallery.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        // Validar archivo
                        if (!in_array($value->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml'])) {
                            $fail("El campo $attribute debe ser una imagen válida (jpg, png, gif o svg).");
                        }
                        if ($value->getSize() > 15360 * 1024) { // 15 MB
                            $fail("El campo $attribute no debe superar los 15 MB.");
                        }
                    } elseif (!is_string($value)) {
                        // Si no es string ni archivo
                        $fail("El campo $attribute debe ser una imagen o una URL válida.");
                    }
                },
            ],
        ];
    }

}
