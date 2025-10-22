<?php

namespace App\Http\Requests\Dashboard\Design;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BackgroundRequest extends FormRequest
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
            'background1' => ['nullable', 'array'],
            'background1.path' => [
                'nullable',
                Rule::when(
                    fn() => $this->hasFile('background1.path'),
                    ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:15360']
                ),
                Rule::when(
                    fn() => is_string($this->input('background1.path')),
                    ['string']
                ),
            ],
            'background1.title' => ['nullable', 'string', 'max:255'],
            'background1.subtitle' => ['nullable', 'string', 'max:255'],

            'background2' => ['nullable', 'array'],
            'background2.path' => [
                'nullable',
                Rule::when(
                    fn() => $this->hasFile('background2.path'),
                    ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:15360']
                ),
                Rule::when(
                    fn() => is_string($this->input('background2.path')),
                    ['string']
                ),
            ],
            'background2.title' => ['nullable', 'string', 'max:255'],
            'background2.subtitle' => ['nullable', 'string', 'max:255'],

            'background3' => ['nullable', 'array'],
            'background3.path' => [
                'nullable',
                Rule::when(
                    fn() => $this->hasFile('background3.path'),
                    ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:15360']
                ),
                Rule::when(
                    fn() => is_string($this->input('background3.path')),
                    ['string']
                ),
            ],
            'background3.title' => ['nullable', 'string', 'max:255'],
            'background3.subtitle' => ['nullable', 'string', 'max:255'],
        ];

    }
}
