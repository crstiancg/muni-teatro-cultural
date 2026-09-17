<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permiso.name' => 'required|unique:permissions,name,' . $this->input('permiso.id'),
            'permiso.description' => 'required',
        ];
    }
}
