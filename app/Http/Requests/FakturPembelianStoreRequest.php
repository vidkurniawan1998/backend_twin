<?php

namespace App\Http\Requests;

use Urameshibr\Requests\FormRequest;

class FakturPembelianStoreRequest extends FormRequest
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
            'no_invoice'            => 'required',
            'tanggal_invoice'       => 'required|date',
            'tanggal_jatuh_tempo'   => 'required|date',
            'disc_persen'           => 'required|numeric|min:0',
            'disc_value'            => 'required|numeric|min:0',
            'status'                => 'required',
            'id_principal'          => 'required|exists:principal,id',
            'id_depo'               => 'required|exists:depo,id',
            'penerimaan_barang'     => 'required|array',
            'penerimaan_barang.*.id'=> 'required|exists:penerimaan_barang,id',
            'id_penerimaan_barang.*.id_barang'  => 'required|exists:barang,id'
        ];
    }

    public function messages()
    {
        return [
            'no_invoice.required'           => 'No invoice wajib isi',
            'tanggal_invoice.required'      => 'Tanggal invoice wajib isi',
            'tanggal_invoice.date'          => 'Format tanggal invoice tidak valid',
            'tanggal_jatuh_tempo.required'  => 'Tanggal jatuh tempo wajib isi',
            'tanggal_jatuh_tempo.date'      => 'Format tanggal jatuh tempo',
            'disc_persen.required'          => 'Diskon persen wajib isi',
            'disc_persen.number'            => 'Format diskon persen tidak valid',
            'disc_persen.min'               => 'Min disc persen 0',
            'disc_value.required'           => 'Diskon rupiah wajib isi',
            'disc_value.number'             => 'Format diskon rupiah tidak valid',
            'disc_value.min'                => 'Min diskon rupiah 0',
            'status.required'               => 'Status wajib isi',
            'id_principal.required'         => 'Principal wajib isi',
            'id_principal.exists'           => 'Principal tidak ditemukan',
            'id_depo.required'              => 'Depo wajib isi',
            'id_depo.exists'                => 'Depo tidak ditemukan',
            'id_penerimaan_barang.required' => 'Penerimaan barang wajib isi',
            'id_penerimaan_barang.array'    => 'Format penerimaan barang tidak valid'
        ];
    }
}
