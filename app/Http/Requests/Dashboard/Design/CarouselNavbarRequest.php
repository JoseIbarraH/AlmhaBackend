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
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.path' => ['required', $this->fileOrUrlRule()],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.subtitle' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function fileOrUrlRule()
    {
        return function ($attribute, $value, $fail) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                // Validar archivo subido
                if (!$value->isValid()) {
                    $fail('El archivo no es válido.');
                    return;
                }

                $allowedMimes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($value->getClientOriginalExtension(), $allowedMimes)) {
                    $fail('Solo se permiten imágenes (jpg, png, gif, webp).');
                    return;
                }

                if ($value->getSize() > 5 * 1024 * 1024) {
                    $fail('La imagen no debe superar los 5MB.');
                }
            } elseif (is_string($value)) {
                // Validar URL existente
                if (!filter_var($value, FILTER_VALIDATE_URL) && !file_exists(public_path($value))) {
                    $fail('La URL proporcionada no es válida.');
                }
            } else {
                $fail('El valor debe ser un archivo o una URL.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe agregar al menos un elemento.',
            'items.min' => 'Debe agregar al menos un elemento.',
            'items.max' => 'No puede agregar más de 10 elementos.',
        ];
    }

}
