<?php


namespace App\Http\Requests;


use Urameshibr\Requests\FormRequest;

class SelectOptionStoreRequest extends FormRequest
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
            'code'  => 'required|string',
            'value' => 'required|string',
            'text'  => 'required|string'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'code.required'     => 'Kode wajib isi',
            'value.required'    => 'Nilai opsi wajib isi',
            'text.required'     => 'Teks opsi wajib isi'
        ];
    }
}
