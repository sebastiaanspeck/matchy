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

    'accepted'             => 'El :attribute debe ser aceptado.',
    'active_url'           => 'El :attribute no es una URL válida.',
    'after'                => 'El :attribute debe ser una fecha posterior a :date.',
    'after_or_equal'       => 'El :attribute debe ser una fecha posterior o igual a :date.',
    'alpha'                => 'El :attribute debe contener solamente letras.',
    'alpha_dash'           => 'El :attribute debe contener solamente letras, números, guiones y guiónes bajos.',
    'alpha_num'            => 'El :attribute debe contener solamente letras y números.',
    'array'                => 'El :attribute debe ser un arreglo.',
    'before'               => 'El :attribute debe ser una fecha anterior a :date.',
    'before_or_equal'      => 'El :attribute debe ser una fecha anterior o igual a :date.',
    'between'              => [
        'numeric' => 'El :attribute debe estar entre :min y :max.',
        'file'    => 'El :attribute debe ser entre :min y :max kilobytes.',
        'string'  => 'El :attribute debe ser entre :min y :max caracteres.',
        'array'   => 'El :attribute debe tener :min y :max elementos.',
    ],
    'boolean'              => 'El :attribute campo debe ser cierto o falso.',
    'confirmed'            => 'El :attribute confirmación no coincide.',
    'date'                 => 'El :attribute no es una fecha válida.',
    'date_format'          => 'El :attribute no coincide con el formato :format.',
    'different'            => 'El :attribute y :other deben ser diferentes.',
    'digits'               => 'El :attribute debe ser de :digits digitos.',
    'digits_between'       => 'El :attribute debe ser de entre :min y :max digitos.',
    'dimensions'           => 'El :attribute tiene dimensiones de imagen inválidas.',
    'distinct'             => 'El :attribute campo tiene un valor duplicado.',
    'email'                => 'El :attribute debe ser una dirección de e-mail válida.',
    'exists'               => 'El :attribute elegido es inválido.',
    'file'                 => 'El :attribute debe ser un archivo.',
    'filled'               => 'El :attribute campo debe tener un valor.',
    'gt'                   => [
        'numeric' => 'El :attribute debe ser mayor que :value.',
        'file'    => 'El :attribute debe ser mayor que :value kilobytes.',
        'string'  => 'El :attribute debe ser mayor que :value caracteres.',
        'array'   => 'El :attribute debe tener más de :value elementos.',
    ],
    'gte'                  => [
        'numeric' => 'El :attribute debe ser mayor o igual que :value.',
        'file'    => 'El :attribute debe ser mayor o igual que :value kilobytes.',
        'string'  => 'El :attribute debe ser mayor o igual que :value characters.',
        'array'   => 'El :attribute debe tener :value elementos o más.',
    ],
    'image'                => 'El :attribute debe ser una imagen.',
    'in'                   => 'El :attribute elegido es inválido.',
    'in_array'             => 'El :attribute campo no existe en :other.',
    'integer'              => 'El :attribute debe ser un entero.',
    'ip'                   => 'El :attribute debe ser una dirección IP válida.',
    'ipv4'                 => 'El :attribute debe ser una dirección IPv4 válida.',
    'ipv6'                 => 'El :attribute debe ser una dirección IPv6 válida.',
    'json'                 => 'El :attribute debe ser una cadena JSON válida.',
    'lt'                   => [
        'numeric' => 'El :attribute debe ser menor que :value.',
        'file'    => 'El :attribute debe ser menor que :value kilobytes.',
        'string'  => 'El :attribute debe ser menor que :value caracteres.',
        'array'   => 'El :attribute debe tener menos de :value elementos.',
    ],
    'lte'                  => [
        'numeric' => 'El :attribute debe ser menor o igual que :value.',
        'file'    => 'El :attribute debe ser menor o igual que :value kilobytes.',
        'string'  => 'El :attribute debe ser menor o igual que :value caracteres.',
        'array'   => 'El :attribute no debe tener más de :value elementos.',
    ],
    'max'                  => [
        'numeric' => 'El :attribute no debe ser mayor que :max.',
        'file'    => 'El :attribute no debe ser mayor que :max kilobytes.',
        'string'  => 'El :attribute no debe ser mayor que :max caracteres.',
        'array'   => 'El :attribute no debe tener más de :max elementos.',
    ],
    'mimes'                => 'El :attribute debe ser un arhico de tipo: :values.',
    'mimetypes'            => 'El :attribute debe ser un archivo de tipos: :values.',
    'min'                  => [
        'numeric' => 'El :attribute debe ser por lo menos :min.',
        'file'    => 'El :attribute debe ser de por lo menos :min kilobytes.',
        'string'  => 'El :attribute debe ser de por lo menos :min caracteres.',
        'array'   => 'El :attribute debe tener por lo menos :min elementos.',
    ],
    'not_in'               => 'El :attribute elegido es inválido.',
    'not_regex'            => 'El :attribute formato es inválido.',
    'numeric'              => 'El :attribute debe ser un número.',
    'present'              => 'El :attribute campo debe estar presente.',
    'regex'                => 'El :attribute formato es inválido.',
    'required'             => 'El :attribute campo es requerido.',
    'required_if'          => 'El :attribute campo es requerido cuando :other es :value.',
    'required_unless'      => 'El :attribute campo es requerido a menos que :other es en  :values.',
    'required_with'        => 'El :attribute campo es requerido cuando :values esté presente.',
    'required_with_all'    => 'El :attribute campo es requerido cuando :values esté presente.',
    'required_without'     => 'El :attribute campo es requerido cuando :values no esté presente.',
    'required_without_all' => 'El :attribute campo es requerido cuando ninguno de los :values estén presentes.',
    'same'                 => 'El :attribute y :other deben coincidir.',
    'size'                 => [
        'numeric' => 'El :attribute debe ser :size.',
        'file'    => 'El :attribute debe ser de :size kilobytes.',
        'string'  => 'El :attribute debe ser de :size caracteres.',
        'array'   => 'El :attribute debe contener :size elementos.',
    ],
    'string'               => 'El :attribute debe ser una cadena de caracteres.',
    'timezone'             => 'El :attribute debe ser una zona válida.',
    'unique'               => 'El :attribute ha sido tomado.',
    'uploaded'             => 'El :attribute falló al cargarse.',
    'url'                  => 'El :attribute formato es inválido.',

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
