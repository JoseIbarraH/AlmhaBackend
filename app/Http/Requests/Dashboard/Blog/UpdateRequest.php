<?php

namespace App\Http\Requests\Dashboard\Blog;

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
            'title' => 'required|string',
            'content' => 'required|string',
            'status' => 'required|in:active,inactive',
            'category' => 'required',
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image_name'),
                    ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:15360']
                ),
                Rule::when(
                    is_string($this->input('image_name')),
                    ['string']
                ),
            ]
        ];
    }
}
