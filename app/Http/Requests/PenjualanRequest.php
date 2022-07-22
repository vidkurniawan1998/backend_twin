<?php

namespace App\Http\Requests;

use Urameshibr\Requests\FormRequest;

class PenjualanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tanggal_jadwal' => 'required|date',
            'driver_id' => 'required|exists:driver,user_id',
        ];
    }

    public function messages()
    {
        return [
            'tanggal_jadwal.required' => 'Data Tanggal Pengiriman Wajib disi',
            'tanggal_jadwal.date' => 'Format tanggal tidak valid',
            'driver_id.driver_id' => 'Driver Pengiriman Wajib disi'
        ];
    }
}