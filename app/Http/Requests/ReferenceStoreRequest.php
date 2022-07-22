<?php

namespace App\Http\Requests;

use Urameshibr\Requests\FormRequest;

class ReferenceStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize():bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules():array
    {
        return [
            'code'  => 'required|unique:references,code',
            'value' => 'required',
            'notes' => 'required'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages():array
    {
        return [
            'code.required' => 'Kode wajib isi',
            'code.unique'   => 'Kode sudah digunakan',
            'notes.required'=> 'Notes wajib isi'
        ];
    }
}
