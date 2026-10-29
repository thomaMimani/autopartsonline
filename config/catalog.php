<?php

return [
    // "fake" = hand-written fixtures (development, tests). "api" = AutoPartsAPI.
    // Only use "api" with storage once the MEGA plan is active (API terms §5).
    'driver' => env('CATALOG_DRIVER', 'fake'),

    'base_url' => env('APIPROFILE_BASE_URL'),
    'api_key' => env('APIPROFILE_API_KEY'),
    'lang_id' => (int) env('APIPROFILE_LANG_ID', 4),
    'country_filter_id' => (int) env('APIPROFILE_COUNTRY_FILTER_ID', 63),
    'type_id' => (int) env('APIPROFILE_TYPE_ID', 1),
    'timeout' => 20,

    'vin_cache_days' => 90,
    'fitment_refresh_days' => 30,
];
