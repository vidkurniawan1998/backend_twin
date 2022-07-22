<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class TargetSalesmanStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_perusahaan'     => 'required|exists:perusahaan,id',
            'mulai_tanggal'     => 'required|date',
            'sampai_tanggal'    => 'required|date|after:mulai_tanggal',
            'hari_kerja'        => 'required|numeric|min:1',
            'salesman'          => 'required|array|min:1',
            'salesman.*.id_user'=> 'required|exists:users,id',
            'salesman.*.id_depo'=> 'required|exists:depo,id',
            'salesman.*.target' => 'required|integer|min:0'
        ];
    }

    public function messages()
    {
        return [
            'id_perusahaan.required'    => 'Perusahaan wajib isi',
            'id_perusahaan.exists'      => 'Perusahaan tidak ditemukan',
            'mulai_tanggal'             => 'Tanggal mulai target wajib isi',
            'mulai_tanggal.date'        => 'Format tanggal mulai tidak sesuai',
            'sampai_tanggal.required'   => 'Tanggal sampai target wajib isi',
            'sampai_tanggal.date'       => 'Format tanggal sampai tidak sesuai',
            'sampai_tanggal.after'      => 'Format tanggal sampai < tanggal mulai',
            'hari_kerja.required'       => 'Jumlah hari kerja wajib isi',
            'hari_kerja.min'            => 'Jumlah hari kerja tidak boleh kurang dari 1'
        ];
    }
}
