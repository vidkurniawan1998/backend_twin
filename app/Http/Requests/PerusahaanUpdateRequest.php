<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class PerusahaanUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->segment(2);
        return [
            'kode_perusahaan' => 'required|max:50|unique:perusahaan,kode_perusahaan,'.$id,
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
