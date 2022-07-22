<?php

/*
|--------------------------------------------------------------------------
| ACCURATE API CREDENTIAL
|--------------------------------------------------------------------------
*/

return [
    'signatureSecretKey' => env('ACCURATE_SIGNATURE_SECRET', ""),
    'session' => env('ACCURATE_SESSION', ""),
    'host' => env('ACCURATE_HOST', "https://public.accurate.id/accurate/api"),
    
    'authBearer' => env('ACCURATE_ACCESS_TOKEN', ""),
];