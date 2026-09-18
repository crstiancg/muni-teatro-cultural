<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMiUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario.name' => 'required',
            'usuario.email' => 'required|email|unique:users,email,' . $this->user()->id,
        ];
    }
}
