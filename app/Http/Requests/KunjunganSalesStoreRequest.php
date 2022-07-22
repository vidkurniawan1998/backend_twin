<?php

namespace App\Http\Requests;

use Urameshibr\Requests\FormRequest;

class KunjunganSalesStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_toko'   => 'required|exists:toko,id',
            'status'    => 'required',
            'latitude'  => 'required',
            'longitude' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'id_toko.required'  => 'Toko wajib isi',
            'id_toko.exists'    => 'Data toko tidak ditemukan',
            'status.required'   => 'Status wajib isi',
            'latitude.required' => 'Latitude wajib isi',
            'longitude.required'=> 'Longitude wajib isi',
        ];
    }
}
