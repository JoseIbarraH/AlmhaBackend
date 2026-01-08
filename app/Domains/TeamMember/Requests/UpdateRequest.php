<?php
namespace App\Domains\TeamMember\Requests;

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
        $teamMemberId = $this->route('id');
        \Log::info('Team ID: ', [$teamMemberId]);
        \Log::info('Team: ', [$this->all()]);

        return [
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'specialization' => 'sometimes|string|max:255',
            'biography' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360',

            'result' => 'sometimes|array',
            'result.deleted' => 'sometimes|array',
            'result.deleted.*' => ['integer', Rule::exists('team_member_images', 'id')->where('team_member_id', $teamMemberId)],

            'result.updated' => 'sometimes|array',
            'result.updated.*.id' => ['required_with:result.updated', 'integer', Rule::exists('team_member_images', 'id')->where('team_member_id', $teamMemberId)],
            'result.updated.*.path' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360',
            'result.updated.*.description' => 'sometimes|string|max:5000',
            'result.updated.*.order' => 'nullable|integer|min:0',

            'result.new' => 'sometimes|array',
            'result.new.*.path' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:15360',
            'result.new.*.description' => 'nullable|string|max:5000',
            'result.new.*.order' => 'nullable|integer|min:0',
        ];
    }
}
