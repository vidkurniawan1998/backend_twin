<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class TokoStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nama_toko' => 'required|max:255',
            'tipe'      => 'required|in:R1,R2,W,MM,KOP,HRC,HCO,SM,PND',
            'tipe_harga'=> 'required|exists:tipe_harga,tipe_harga',
            'pemilik'   => 'max:255',
            'no_acc'    => 'max:20',
            'kode_mars' => 'max:20|nullable|unique:toko',
            'telepon'   => 'required_if:k_t,kredit|max:20',
            'alamat'    => 'required',
            'kode_pos'  => 'required|numeric|max:99999',
            'k_t'       => 'required|in:kredit,tunai',
            'nama_pkp'  => 'required_with:npwp',
            'alamat_pkp'=> 'required_with:npwp',
            'limit'     => 'numeric|min:0|max:99999999999999999999',
            'minggu'    => 'in:1&3,2&4,1-4,1,2,3,4',
            'hari'      => 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'id_tim'    => 'required|min:0|max:9999999999',
            'id_depo'   => 'required|exists:depo,id',
            'nama_ktp'  => 'required_with:no_ktp',
            'alamat_ktp'=> 'required_with:no_ktp',
            // 'id_principal' => 'nullable|exists:principal,id',
            'tipe_2'    => 'nullable',
            'id_kelurahan' => 'required|exists:kelurahan,id',
            'lock_order' => 'in:0,1'
        ];
    }

    public function messages()
    {
        return [
            'nama_toko.required'    => 'Nama toko wajib isi',
            'tipe.required'         => 'Tipe toko wajib isi',
            'tipe_harga.required'   => 'Tipe harga wajib isi',
            'telepon.required_if'   => 'Telepon toko wajib isi jika pembayaran kredit',
            'alamat.required'       => 'Alamat wajib isi',
            'k_t.in'                => 'Ketentuan pembayaran harus kredit atau tunai',
            'nama_pkp.required_with'=> 'Nama PKP tidak boleh kosong jika npwp isi',
            'alamat_pkp.required_with'=> 'Alamat PKP tidak boleh kosong jika npwp isi',
            'limit.numeric'         => 'Format limit tidak valid',
            'minggu.in'             => 'Minggu kunjungan wajib isi',
            'hari.in'               => 'Hari kunjungan wajib isi',
            'id_tim.required'       => 'Tim wajib isi',
            'id_depo.required'      => 'Depo wajib isi',
            'nama_ktp.required_with'=> 'Nama KTP wajib isi jika No KTP isi',
            'alamat_ktp.required_with' => 'Alamat KTP wajib isi jika No KTP isi',
            'id_kelurahan.required' => 'Kelurahan wajib isi',
            'kode_pos.required'     => 'Kode Pos wajib isi'
        ];
    }
}
