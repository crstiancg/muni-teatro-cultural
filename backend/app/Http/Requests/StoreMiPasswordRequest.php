<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class StoreMiPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password.actual' => 'required',
            'password.nueva' => 'required|min:8|confirmed',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (!Hash::check(data_get($this, 'password.actual'), $this->user()->password)) {
                $validator->errors()->add('password.actual', 'La contraseña actual no es correcta.');
            }
        });
    }
}
