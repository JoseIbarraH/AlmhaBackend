<?php

namespace App\Http\Requests\Dashboard\Design;

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
    public function rules(): array
    {
        return [
            'path' => [
                Rule::when(
                    fn() => $this->hasFile('path'),
                    ['file', 'mimes:jpeg,png,jpg,gif,svg,webp,mp4,webm,mov,avi,ogg', 'max:20480']
                ),
                Rule::when(
                    fn() => is_string($this->input('path')),
                    ['string']
                ),
            ],
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
        ];
    }
}
