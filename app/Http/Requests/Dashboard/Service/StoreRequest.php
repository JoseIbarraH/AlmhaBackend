<?php

namespace App\Http\Requests\Dashboard\Service;

use Illuminate\Foundation\Http\FormRequest;

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
            'status' => 'required|string',
            'title' => 'required|string',
            'description' => 'required|string',

            // Fases quirúrgicas
            'surgery_phases' => 'array|nullable',
            'surgery_phases.*.recovery_time' => 'nullable|string',
            'surgery_phases.*.preoperative_recommendations' => 'nullable|string',
            'surgery_phases.*.postoperative_recommendations' => 'nullable|string',

            // Preguntas frecuentes
            'frequently_asked_questions' => 'array|nullable',
            'frequently_asked_questions.*.question' => 'nullable|string',
            'frequently_asked_questions.*.answer' => 'nullable|string',

            // Imágenes de muestra

            'sample_images' => 'array|nullable',
            'sample_images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

            'sample_images.*.recovery' => 'nullable|string',
            'sample_images.*.postoperative_care' => 'nullable|string',
            'sample_images.*.image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

            // Galería de resultados
            'result_images' => 'array|nullable',
            'result_images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',



        ];
    }


}