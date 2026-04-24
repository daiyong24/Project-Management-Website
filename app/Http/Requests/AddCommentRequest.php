<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages()
    {
        return [
            'note.required' => 'Comment cannot be empty.',
            'note.max' => 'Comment must be 1000 characters or less.',
        ];
    }
}
