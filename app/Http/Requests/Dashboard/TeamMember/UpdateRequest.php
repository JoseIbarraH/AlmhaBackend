<?php

namespace App\Http\Requests\Dashboard\TeamMember;

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
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'specialization' => 'required|string|max:300',
            'biography' => 'required|string',
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image'),
                    ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5120']
                ),
                Rule::when(
                    is_string($this->input('image')),
                    ['string']
                ),
            ],
            'results' => 'nullable'
        ];
    }
}
