<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
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
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages()
    {
        return [
            'project_id.required' => 'Please select a project.',
            'project_id.exists' => 'The selected project does not exist.',
            'assigned_to_user_id.required' => 'Please assign the activity to someone.',
            'assigned_to_user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
