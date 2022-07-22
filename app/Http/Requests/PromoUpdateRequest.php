<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class PromoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'no_promo'      => 'required|max:100',
            'nama_promo'    => 'required|max:255',
            'status'        => 'required|in:active,non_active',
            'status_klaim'  => 'required|in:0,1',
            'disc_rupiah'   => 'numeric|min:0|max:99999999999999999999',
            'pcs_extra'     => 'numeric|min:0|max:99999999999999999999',
            'tanggal_awal'  => 'required|date',
            'tanggal_akhir' => 'required|date|gte:tanggal_awal',
            'depo'          => 'required|array|min:1',
            'barang'        => 'nullable|array',
            'toko'          => 'nullable|array'
        ];
    }

    public function messages()
    {
        return [
            'no_promo.required'     => 'No Proposal wajib isi',
            'nama_promo.required'   => 'Nama promo wajib isi',
            'status.required'       => 'Status wajib isi',
            'status_klaim.required' => 'Status klaim wajib isi',
            'disc_rupiah.numeric'   => 'Diskon rupiah tidak valid',
            'pcs_extra.numeric'     => 'Jumlah barang ekstra tidak valid',
            'tanggal_awal.required' => 'Tanggal awal wajib isi',
            'tanggal_awal.date'     => 'Format tanggal awal tidak valid',
            'tanggal_akhir.required'=> 'Tanggal akhir wajib isi',
            'tanggal_akhir.date'    => 'Format tanggal akhir tidak valid',
            'tanggal_akhir.gte'     => 'Tanggal akhir tidak boleh lebih kecil dari tanggal akhir',
            'depo.required'         => 'Depo wajib isi',
            'barang.array'          => 'Format data barang tidak valid',
            'toko.array'            => 'Format data toko tidak valid'
        ];
    }
}
