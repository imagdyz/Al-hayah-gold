<?php

return [

    // Where the 24k base price comes from: "manual", "daleelak" or "http".
    'source' => env('GOLD_PRICE_SOURCE', 'manual'),

    // getdaleelak.com: free feed of Egypt's licensed gold and currency prices.
    // The base price is the mid of the best buy and sell, so our own margins
    // are not stacked on top of a shop's margin.
    'daleelak' => [
        'url' => env('DALEELAK_URL', 'https://getdaleelak.com/api/v1/feed.json'),
        'category' => env('DALEELAK_CATEGORY', 'metals'),
        'asset_24' => env('DALEELAK_ASSET_24', 'gold-24k'),
        'timeout' => 10,
    ],

    // A fetched price further than this from the last one is not saved and
    // online orders are halted until someone checks it.
    'max_jump_percent' => (float) env('GOLD_MAX_JUMP_PERCENT', 3),

    // Online orders halt when an automatic source has not updated for this long.
    'stale_minutes' => (int) env('GOLD_STALE_MINUTES', 15),

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
        // Show the code on the login page outside local too. For a preview
        // server only, until an SMS provider is wired in.
        'show_code' => (bool) env('OTP_SHOW_CODE', false),
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_seconds' => 60,
    ],

];
