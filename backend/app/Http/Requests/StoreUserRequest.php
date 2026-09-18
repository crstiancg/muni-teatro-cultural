<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . optional($this->route('usuario'))->id,
            'password' => $this->route('usuario') ? 'nullable|min:8' : 'required|min:8',
        ];
    }
}
