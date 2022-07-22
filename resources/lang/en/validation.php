<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'accepted'             => 'The :attribute must be accepted.',
    'active_url'           => 'The :attribute is not a valid URL.',
    'after'                => 'The :attribute must be a date after :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => 'The :attribute may only contain letters.',
    'alpha_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num'            => 'The :attribute may only contain letters and numbers.',
    'array'                => 'The :attribute must be an array.',
    'before'               => 'The :attribute must be a date before :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => 'Kolom :attribute hanya menerima angka diantara :min sampai :max.',
        'file'    => 'Kolom :attribute hanya menerima file dengan ukuran :min sampai :max kb.',
        'string'  => 'Kolom :attribute hanya menerima data :min sampai :max karakter.',
        'array'   => 'Kolom :attribute hanya menerima data diantara :min sampai :max item.',
    ],
    'boolean'              => 'Kolom :attribute harus bernilai true atau false.',
    'confirmed'            => 'Kolom :attribute tidak sesuai.',
    'date'                 => 'Kolom :attribute hanya menerima data tanggal.',
    'date_format'          => 'Kolom :attribute tidak sesuai dengan format :format.',
    'different'            => 'Kolom :attribute dan :other harus berbeda.',
    'digits'               => 'Kolom :attribute hanya menerima data :digits digit.',
    'digits_between'       => 'Kolom :attribute hanya menerima data diantara :min sampai :max digit.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'The :attribute field has a duplicate value.',
    'email'                => 'Kolom :attribute harus diisi dengan format email yang benar.',
    'exists'               => 'Kolom :attribute tidak sesuai.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field is required.',
    'image'                => 'Anda hanya boleh memilih file berformat gambar.',
    'in'                   => 'Pilihan yang anda pilih salah.',
    'in_array'             => 'The :attribute field does not exist in :other.',
    'integer'              => 'Kolom :attribute harus diisi dengan angka.',
    'ip'                   => 'The :attribute must be a valid IP address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'file'    => 'The :attribute may not be greater than :max kilobytes.',
        'string'  => 'Kolom :attribute tidak boleh lebih banyak dari :max karakter.',
        'array'   => 'The :attribute may not have more than :max items.',
    ],
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'Kolom :attribute tidak boleh lebih kecil dari :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'Kolom :attribute harus diisi minimal :min karakter.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
    'not_in'               => 'The selected :attribute is invalid.',
    'numeric'              => 'Kolom :attribute hanya menerima data angka.',
    'present'              => 'The :attribute field must be present.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'Kolom :attribute harus diisi.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values is present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'Kolom :attribute dan :other harus sama.',
    'size'                 => [
        'numeric' => 'Kolom :attribute hanya menerima data sebanyak :size digit.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'Kolom :attribute hanya menerima data sebanyak :size karakter.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'               => 'Kolom :attribute hanya menerima data berupa teks.',
    'timezone'             => 'The :attribute must be a valid zone.',
    'unique'               => 'Data pada kolom :attribute telah digunakan.',
    'uploaded'             => 'The :attribute failed to upload.',
    'url'                  => 'The :attribute format is invalid.',
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
    'attributes' => [],
];