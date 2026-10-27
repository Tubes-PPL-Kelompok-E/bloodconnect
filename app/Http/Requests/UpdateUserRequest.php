<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'role'     => ['required', 'in:admin_pmi,petugas_rs,pendonor'],
            'instansi' => ['required', 'string', 'max:150'],
        ];
    }
}
