<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class PerusahaanStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'kode_perusahaan' => 'required|unique:perusahaan|max:50',
            'nama_perusahaan' => 'required|max:255'
        ];
    }

    public function messages()
    {
        return [
            'kode_perusahaan.required'  => 'Kode perusahaan wajib isi',
            'kode_perusahaan.max'       => 'Kode perusahaan maksimal 50 karakter',
            'nama_perusahaan.required'  => 'Nama perusahaan wajib isi',
            'nama_perusahaan.max'       => 'Nama perusahaan maksimal 255 karakter'
        ];
    }
}
