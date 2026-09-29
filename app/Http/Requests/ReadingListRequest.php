<?php

namespace App\Http\Requests;

use App\Models\ReadingList;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReadingListRequest extends FormRequest
{
    /**
     * Ownership is checked before validation, so non-owners get a 404 whatever they submit.
     */
    public function authorize(): Response
    {
        $list = $this->route('list');

        return $list instanceof ReadingList ? Gate::inspect('update', $list) : Response::allow();
    }

    /**
     * Browsers submit textarea line breaks as CRLF; store (and count) each as a single character.
     */
    protected function prepareForValidation(): void
    {
        $description = $this->input('description');
        if (is_string($description)) {
            $this->merge(['description' => str_replace("\r\n", "\n", $description)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // Optional: blank input arrives as null (ConvertEmptyStringsToNull), which also clears it on edit.
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
