<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'daily_token_limit' => env('OPENAI_DAILY_TOKEN_LIMIT', 2500000),
        'location_report_model' => env('OPENAI_LOCATION_REPORT_MODEL', 'gpt-5-mini'),
        'location_report_max_completion_tokens' => env('OPENAI_LOCATION_REPORT_MAX_COMPLETION_TOKENS', 800),
        'location_report_prompt_max_points' => env('OPENAI_LOCATION_REPORT_PROMPT_MAX_POINTS', 20),
        'location_report_max_fields_per_point' => env('OPENAI_LOCATION_REPORT_MAX_FIELDS_PER_POINT', 12),
        'location_report_max_value_length' => env('OPENAI_LOCATION_REPORT_MAX_VALUE_LENGTH', 160),
    ],

    'reports' => [
        'snapshot_url_ttl_minutes' => env('REPORT_SNAPSHOT_URL_TTL_MINUTES', 15),
        'email_map_days' => env('REPORT_EMAIL_MAP_DAYS', 2),
        'email_map_fallback_days' => env('REPORT_EMAIL_MAP_FALLBACK_DAYS', 7),
        'email_map_limit' => env('REPORT_EMAIL_MAP_LIMIT', 4),
        'email_map_radius' => env('REPORT_EMAIL_MAP_RADIUS', 0.25),
    ],

    'playwright' => [
        'node_path' => env('PLAYWRIGHT_NODE_PATH'),
        'browsers_path' => env('PLAYWRIGHT_BROWSERS_PATH'),
        'library_path' => env('PLAYWRIGHT_LIBRARY_PATH', storage_path('app/playwright-libs/lib64')),
        'timeout_ms' => env('PLAYWRIGHT_TIMEOUT_MS', 45000),
        'viewport_width' => env('PLAYWRIGHT_VIEWPORT_WIDTH', 1400),
        'viewport_height' => env('PLAYWRIGHT_VIEWPORT_HEIGHT', 900),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'bostongov' => [
        'api_key' => env('BOSTON_API_KEY'),
        'base_url' => env('BOSTON_311_BASE_URL', 'https://311.boston.gov/open311/v2'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'google_geocoding' => [
        'api_key' => env('GOOGLE_GEOCODING_API_KEY'),
    ],

    'scraper_service' => [
        'base_url' => env('SCRAPER_API_BASE_URL', 'http://localhost:8000'),
        'user_id' => env('SCRAPER_X_USER_ID', '1'),
        'user_name' => env('SCRAPER_X_USER_NAME', 'Guest'),
        'user_role' => env('SCRAPER_X_USER_ROLE', 'guest'),
        'wait_seconds' => env('SCRAPER_WAIT_SECONDS', '5'),
    ],

    'analysis_api' => [
        'url' => env('ANALYSIS_API_URL'),
    ],

    's3' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('S3_BUCKET_NAME'),
    ],
];
