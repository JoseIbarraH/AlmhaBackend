<?php

namespace App\Domains\Procedure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedureRequest extends FormRequest
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
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360',
            'status' => 'sometimes|in:draft,published,archived',
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'sometimes|string|max:255',

            'faq' => 'sometimes|array',
            'faq.*.question' => 'sometimes|string|max:500',
            'faq.*.answer' => 'sometimes|string|max:5000',

            'postImage' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360',

            'do' => 'sometimes|array',
            'do.*.title' => 'sometimes|string|max:255',
            'do.*.description' => 'sometimes|string|max:5000',
            'do.*.order' => 'sometimes|integer',

            'dont' => 'sometimes|array',
            'dont.*.title' => 'sometimes|string|max:255',
            'dont.*.description' => 'sometimes|string|max:5000',
            'dont.*.order' => 'sometimes|integer',
        ];
    }
}
