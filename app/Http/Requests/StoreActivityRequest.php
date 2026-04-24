<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'task_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_user_id' => ['required', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['Pending', 'In Progress', 'Completed'])],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages()
    {
        return [
            'project_id.required' => 'Please select a project.',
            'project_id.exists' => 'The selected project does not exist.',
            'type.in' => 'Invalid activity type.',
            'status.in' => 'Invalid status value.',
            'assigned_to_user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
