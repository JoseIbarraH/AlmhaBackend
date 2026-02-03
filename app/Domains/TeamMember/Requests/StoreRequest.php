<?php

namespace App\Domains\TeamMember\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
    public function rules(): array
    {
        \Log::info("Datos: ", [$this->all()]);

        return [
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'specialization' => 'sometimes|string|max:255',
            'biography' => 'sometimes|string',
            'description' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360',

            'result' => 'sometimes|array',
            'result.*.path' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:15360',
            'result.*.description' => 'nullable|string|max:5000',
        ];
    }
}
