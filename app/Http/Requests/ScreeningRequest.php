<?php

namespace App\Http\Requests;

use App\Models\BloodBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $current = $this->route('screening');
        $result = ['reaktif', 'non_reaktif'];

        $rules = [
            'report_number' => ['required', 'string', 'max:100',
                Rule::unique('screening_results', 'report_number')->ignore($current?->id)],
            'hiv' => ['required', Rule::in($result)],
            'hbsag' => ['required', Rule::in($result)],
            'hcv' => ['required', Rule::in($result)],
            'sifilis' => ['required', Rule::in($result)],
            'tested_at' => ['required', 'date', 'before_or_equal:today'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($isUpdate) {
            // Koreksi wajib menyertakan alasan
            $rules['correction_reason'] = ['required', 'string', 'min:5', 'max:1000'];
        } else {
            $rules['blood_bag_id'] = ['required',
                Rule::exists('blood_bags', 'id')->where('status', BloodBag::KARANTINA),
                Rule::unique('screening_results', 'blood_bag_id')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'blood_bag_id.exists' => 'Kantong tidak ditemukan atau tidak berstatus Karantina.',
            'blood_bag_id.unique' => 'Kantong ini sudah memiliki hasil uji saring.',
            'report_number.unique' => 'Nomor berita acara sudah digunakan.',
            'correction_reason.required' => 'Alasan koreksi wajib diisi.',
            'correction_reason.min' => 'Alasan koreksi minimal 5 karakter.',
            'file.mimes' => 'Berkas harus berformat PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
        ];
    }
}