<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class SharingPromosStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_promo' => 'required|exists:promo,id',
            'persen_principal' => 'required|numeric|min:0|max:100',
            'persen_dist' => 'required|numeric|min:0|max:100',
            'nominal_principal' => 'required|numeric|min:0|max:100',
            'nominal_dist' => 'required|numeric|min:0|max:100',
            'extra_principal' => 'required|numeric|min:0|max:100',
            'extra_dist' => 'required|numeric|min:0|max:100',
        ];
    }

    public function messages()
    {
        return [
            'id_promo.required' => 'Promo wajib isi',
            'id_promo.exists' => 'Promo tidak ditemukan',

            'persen_dist.required' => 'Diskon persen distributor wajib isi',
            'persen_dist.numeric' => 'Format diskon persen distributor tidak valid',
            'persen_dist.min' => 'Minimal diskon persen distributor min 0',
            'persen_dist.max' => 'Minimal diskon persen distributor max 100',

            'persen_principal.required' => 'Diskon persen principal wajib isi',
            'persen_principal.numeric' => 'Format diskon persen principal tidak valid',
            'persen_principal.min' => 'Minimal diskon persen principal min 0',
            'persen_principal.max' => 'Minimal diskon persen principal max 100',

            'nominal_dist.required' => 'Diskon nominal distributor wajib isi',
            'nominal_dist.numeric' => 'Format diskon nominal distributor tidak valid',
            'nominal_dist.min' => 'Minimal diskon nominal distributor nominal min 0',
            'nominal_dist.max' => 'Minimal diskon nominal distributor nominal max 100',

            'nominal_principal.required' => 'Diskon nominal principal wajib isi',
            'nominal_principal.numeric' => 'Format diskon nominal principal tidak valid',
            'nominal_principal.min' => 'Minimal diskon nominal principal min 0',
            'nominal_principal.max' => 'Minimal diskon nominal principal max 100',

            'extra_principal.required' => 'Persentase extra barang principal wajib isi',
            'extra_principal.numeric' => 'Format persentase extra barang principal tidak valid',
            'extra_principal.min' => 'Minimal persentase extra barang principal min 0',
            'extra_principal.max' => 'Minimal persentase extra barang principal max 100',

            'extra_dist.required' => 'Persentase extra barang distributor wajib isi',
            'extra_dist.numeric' => 'Format persentase extra barang distributor tidak valid',
            'extra_dist.min' => 'Minimal persentase extra barang distributor min 0',
            'extra_dist.max' => 'Minimal persentase extra barang distributor max 100',
        ];
    }
}