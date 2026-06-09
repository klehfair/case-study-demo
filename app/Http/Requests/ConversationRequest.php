<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'seller_id'  => ['required', 'integer', 'exists:sellers,id'],
            'session_id' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'message'    => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
