<?php

return [

    // Where the 24k spot price comes from: "manual" or "http".
    'source' => env('GOLD_PRICE_SOURCE', 'manual'),

    'http' => [
        'url' => env('GOLD_PRICE_URL'),
        'token' => env('GOLD_PRICE_TOKEN'),
        // Dot path to the EGP price of one gram of 24k in the JSON response.
        'path' => env('GOLD_PRICE_PATH', 'price_gram_24k'),
    ],

    // A gold pound (جنيه ذهب) is 8 grams of 21k.
    'coin_grams' => 8,
    'coin_karat' => 21,

    // Customer prices are rounded to the nearest multiple of this (EGP).
    'rounding' => 5,

    'otp' => [
        'driver' => env('OTP_DRIVER', 'log'),
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_seconds' => 60,
    ],

];
