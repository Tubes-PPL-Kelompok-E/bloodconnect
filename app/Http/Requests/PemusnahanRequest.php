<?php

namespace App\Http\Requests;

use App\Models\KantongDarah;
use App\Models\PemusnahanKantong;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PemusnahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:admin_pmi
    }

    public function rules(): array
    {
        $pemusnahan = $this->route('pemusnahan');
        $kantong = $pemusnahan
            ? $pemusnahan->kantong
            : KantongDarah::find($this->input('kantong_id'));

        $tanggal = $this->input('tanggal_pemusnahan');
        $dini = $kantong && $tanggal && strtotime($tanggal) !== false
            && PemusnahanKantong::apakahSebelumKedaluwarsa($kantong, $tanggal);

        $rules = [
            'tanggal_pemusnahan' => ['required', 'date', 'before_or_equal:today'],
            'alasan'             => ['required', 'string', 'min:5', 'max:1000'],
            'alasan_khusus'      => [Rule::requiredIf($dini), 'nullable', 'string', 'min:10', 'max:1000'],
        ];

        if (! $pemusnahan) {
            $rules['kantong_id'] = [
                'required',
                Rule::exists('kantong_darah', 'id')->whereIn('status', PemusnahanKantong::BISA_DIMUSNAHKAN),
                Rule::unique('pemusnahan_kantong', 'kantong_id'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'kantong_id.required'                => 'Pilih kantong yang akan dimusnahkan.',
            'kantong_id.exists'                  => 'Kantong tidak ditemukan atau statusnya tidak dapat dimusnahkan.',
            'kantong_id.unique'                  => 'Kantong ini sudah memiliki catatan pemusnahan.',
            'tanggal_pemusnahan.before_or_equal' => 'Tanggal pemusnahan tidak boleh melebihi hari ini.',
            'alasan.required'                    => 'Alasan pemusnahan wajib diisi.',
            'alasan_khusus.required'             => 'Kantong belum kedaluwarsa, alasan khusus wajib diisi.',
            'alasan_khusus.min'                  => 'Alasan khusus minimal 10 karakter.',
        ];
    }
}