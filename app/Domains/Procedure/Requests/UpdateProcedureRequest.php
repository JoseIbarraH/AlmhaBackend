<?php


namespace App\Domains\Procedure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Log;

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

        $procedureId = $this->route('id');
        $data = $this->all();

        Log::info('Procedure Id: ', [$data]);

        return [
            'status' => 'sometimes|in:draft,published,archived', // Estado del procedimiento
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360', // Imagen principal

            'title' => 'sometimes|string|max:255', // Titulo del procedimiento
            'subtitle' => 'sometimes|string|max:255', // Subtitulo del procedimiento

            'section' => 'sometimes|array',
            'section.*.type' => 'required_with:section|in:what_is,technique,recovery',
            'section.*.image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:15360',
            'section.*.title' => 'sometimes|string|max:255',
            'section.*.contentOne' => 'sometimes|nullable|string|max:5000',
            'section.*.contentTwo' => 'sometimes|nullable|string|max:5000',

            // Preparation Steps
            'preStep' => 'sometimes|array',

            'preStep.deleted' => 'sometimes|array',
            'preStep.deleted.*' => ['integer', Rule::exists('procedure_preparation_steps', 'id')->where('procedure_id', $procedureId)],

            'preStep.updated' => 'sometimes|array',
            'preStep.updated.*.id' => ['required', 'integer', Rule::exists('procedure_preparation_steps', 'id')->where('procedure_id', $procedureId)],
            'preStep.updated.*.title' => 'nullable|string|max:255',
            'preStep.updated.*.description' => 'nullable|string|max:5000',
            'preStep.updated.*.order' => 'nullable|integer',

            'preStep.new' => 'sometimes|array',
            'preStep.new.*.title' => 'required|string|max:255',
            'preStep.new.*.description' => 'nullable|string|max:5000',
            'preStep.new.*.order' => 'nullable|integer|min:0',

            // Phases
            'phase' => 'sometimes|array',

            'phase.deleted' => 'sometimes|array',
            'phase.deleted.*' => ['integer', Rule::exists('procedure_recovery_phases', 'id')->where('procedure_id', $procedureId)],

            'phase.updated' => 'sometimes|array',
            'phase.updated.*.id' => ['required_with:phase.updated', Rule::exists('procedure_recovery_phases', 'id')->where('procedure_id', $procedureId)],
            'phase.updated.*.period' => 'nullable|string|max:255',
            'phase.updated.*.title' => 'nullable|string|max:255',
            'phase.updated.*.description' => 'nullable|string|max:5000',
            'phase.updated.*.order' => 'nullable|integer|min:0',

            'phase.new' => 'sometimes|array',
            'phase.new.*.period' => 'required|string|max:255',
            'phase.new.*.title' => 'required|string|max:255',
            'phase.new.*.description' => 'nullable|string|max:5000',
            'phase.new.*.order' => 'nullable|integer|min:0',

            // FAQs
            'faq' => 'sometimes|array',

            'faq.deleted' => 'sometimes|array',
            'faq.deleted.*' => ['integer', Rule::exists('procedure_faqs', 'id')->where('procedure_id', $procedureId)],

            'faq.updated' => 'sometimes|array',
            'faq.updated.*.id' => ['required_with:faq.updated', Rule::exists('procedure_faqs', 'id')->where('procedure_id', $procedureId)],
            'faq.updated.*.question' => 'nullable|string|max:500',
            'faq.updated.*.answer' => 'nullable|string|max:5000',
            'faq.updated.*.order' => 'nullable|integer|min:0',

            'faq.new' => 'sometimes|array',
            'faq.new.*.question' => 'required|string|max:500',
            'faq.new.*.answer' => 'required|string|max:5000',
            'faq.new.*.order' => 'nullable|integer|min:0',

            // Lo que SI debe hacer
            'do' => 'sometimes|array',

            'do.deleted' => 'sometimes|array',
            'do.deleted.*' => ['integer', Rule::exists('procedure_postoperative_instructions', 'id')->where('type', 'do')],

            'do.updated' => 'sometimes|array',
            'do.updated.*.id' => [
                'required_with:do.updated',
                'integer',
                Rule::exists('procedure_postoperative_instructions', 'id')->where('type', 'do')
            ],
            'do.updated.*.content' => 'nullable|string|max:5000',
            'do.updated.*.order' => 'nullable|integer|min:0',

            'do.new' => 'sometimes|array',
            'do.new.*.content' => 'nullable|string|max:5000',
            'do.new.*.order' => 'nullable|integer|min:0',

            // Lo que NO debe hacer
            'dont' => 'sometimes|array',

            'dont.deleted' => 'sometimes|array',
            'dont.deleted.*' => ['integer', Rule::exists('procedure_postoperative_instructions', 'id')->where('type', 'dont')],

            'dont.updated' => 'sometimes|array',
            'dont.updated.*.id' => [
                'required_with:dont.updated',
                'integer',
                Rule::exists('procedure_postoperative_instructions', 'id')
                    ->where('type', 'dont')
            ],
            'dont.updated.*.content' => 'nullable|string|max:5000',
            'dont.updated.*.order' => 'nullable|integer|min:0',

            'dont.new' => 'sometimes|array',
            'dont.new.*.content' => 'nullable|string|max:5000',
            'dont.new.*.order' => 'nullable|integer|min:0',

            // Gallery
            'gallery' => 'sometimes|array',

            'gallery.deleted' => 'sometimes|array',
            'gallery.deleted.*' => ['integer', Rule::exists('procedure_result_galleries', 'id')->where('procedure_id', $procedureId)],

            'gallery.updated' => 'sometimes|array',
            'gallery.updated.*.id' => ['required_with:gallery.updated', 'integer', Rule::exists('procedure_result_galleries', 'id')->where('procedure_id', $procedureId)],
            'gallery.updated.*.path' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:15360',

            'gallery.new' => 'sometimes|array',
            'gallery.new.*.path' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:15360',

        ];
    }

    
}
