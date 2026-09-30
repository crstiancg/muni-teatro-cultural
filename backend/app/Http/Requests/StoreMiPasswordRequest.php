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
            'password.nueva' => [
                'required',
                'min:8',
                'confirmed',
                // el DNI es la clave inicial: volver a usarlo anula el cambio
                function (string $atributo, mixed $valor, \Closure $fail) {
                    if ($valor === $this->user()->persona?->dni) {
                        $fail('La nueva contraseña no puede ser tu DNI.');
                    }
                },
            ],
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
