<?php

namespace App\Http\Requests;

use App\Models\ReadingList;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Adds a search result to a list. Ownership is checked before validation, so non-owners get a 404 whatever they
 * submit.
 */
class BookRequest extends FormRequest
{
    public function authorize(): Response
    {
        $list = $this->route('list');

        return $list instanceof ReadingList ? Gate::inspect('update', $list) : Response::deny();
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'key' => ['nullable', 'string', 'max:200'],
            'title' => ['required', 'string', 'max:500'],
            'authors' => ['present', 'array', 'max:10'],
            'authors.*' => ['string', 'max:200'],
            'year' => ['nullable', 'integer', 'between:-9999,9999'],
        ];
    }
}
