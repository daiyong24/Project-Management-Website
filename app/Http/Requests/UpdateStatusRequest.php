<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['required', Rule::in(['Pending', 'In Progress', 'Completed'])],
        ];
    }

    public function messages()
    {
        return [
            'status.in' => 'Invalid status value.',
        ];
    }
}
