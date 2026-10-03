<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'list', 'min:1'],
            'task_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
