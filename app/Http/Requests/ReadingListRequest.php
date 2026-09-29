<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReadingListRequest extends FormRequest
{
    /**
     * Ownership is checked before validation, so non-owners get a 404 whatever they submit.
     */
    public function authorize(): Response
    {
        $list = $this->route('list');

        return Response::allow();
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
        ];
    }
}
