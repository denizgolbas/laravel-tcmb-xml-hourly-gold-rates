<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | The URL to fetch standard gold rates from TCMB.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | The Base URL to fetch gold rates from TCMB (Reeskont Kurları).
    |
    */
    'base_url' => env('TCMB_GOLD_BASE_URL', 'https://www.tcmb.gov.tr/reeskontkur'),

    /*
    |--------------------------------------------------------------------------
    | Check Hours
    |--------------------------------------------------------------------------
    |
    | Hours to check for the XML file availability (e.g. 12:00, 14:00).
    |
    */
    'check_hours' => ['12:00', '14:00', '16:00'],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Duration in minutes to cache the responses.
    |
    */
    'cache_driver' => env('TCMB_GOLD_CACHE_DRIVER', 'file'),
    'cache_duration' => 120, // 2 hours as per reference code
    'cache_prefix' => 'tcmb_gold_',
];
