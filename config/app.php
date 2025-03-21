<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'local'),

    'app_url' => env('APP_ENV') === 'ngrok'
        ? env('APP_URL_NGROK')
        : (env('APP_ENV') === 'production' ? env('APP_URL_PRODUCTION') : env('APP_URL_LOCAL')),

    'frontend_url' => env('APP_ENV') === 'ngrok'
        ? env('FRONTEND_URL_NGROK')
        : (env('APP_ENV') === 'production' ? env('FRONTEND_URL_PRODUCTION') : env('FRONTEND_URL_LOCAL')),

    'google_redirect_uri' => env('APP_ENV') === 'ngrok'
        ? env('GOOGLE_REDIRECT_URI_NGROK')
        : (env('APP_ENV') === 'production' ? env('GOOGLE_REDIRECT_URI_PRODUCTION') : env('GOOGLE_REDIRECT_URI_LOCAL')),

    'yahoo_redirect_uri' => env('APP_ENV') === 'ngrok'
        ? env('YAHOO_REDIRECT_URI_NGROK')
        : (env('APP_ENV') === 'production' ? env('YAHOO_REDIRECT_URI_PRODUCTION') : env('YAHOO_REDIRECT_URI_LOCAL')),


    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'api_keys' => [
        'splp' => env('SPLP_API_KEY', 'eyJ4NXQiOiJOVGRtWmpNNFpEazNOalkwWXpjNU1tWm1PRGd3TVRFM01XWXdOREU1TVdSbFpEZzROemM0WkE9PSIsImtpZCI6ImdhdGV3YXlfY2VydGlmaWNhdGVfYWxpYXMiLCJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJkaXNrb21pbmZvX3RyZW5nZ2FsZWtrYWJAY2FyYm9uLnN1cGVyIiwiYXBwbGljYXRpb24iOnsib3duZXIiOiJkaXNrb21pbmZvX3RyZW5nZ2FsZWtrYWIiLCJ0aWVyUXVvdGFUeXBlIjpudWxsLCJ0aWVyIjoiMTBQZXJNaW4iLCJuYW1lIjoiQUJTRU5TSSBBUEVMIiwiaWQiOjUyNzcsInV1aWQiOiI5YzQwODdhOS1jNDgzLTRmNjgtYTcxNi01ZDRmZDdmN2I2YTgifSwiaXNzIjoiaHR0cHM6XC9cL3NwbHAubGF5YW5hbi5nby5pZDo0NDNcL29hdXRoMlwvdG9rZW4iLCJ0aWVySW5mbyI6eyJVbmxpbWl0ZWQiOnsidGllclF1b3RhVHlwZSI6InJlcXVlc3RDb3VudCIsImdyYXBoUUxNYXhDb21wbGV4aXR5IjowLCJncmFwaFFMTWF4RGVwdGgiOjAsInN0b3BPblF1b3RhUmVhY2giOmZhbHNlLCJzcGlrZUFycmVzdExpbWl0IjoxMDAwMDAsInNwaWtlQXJyZXN0VW5pdCI6InNlYyJ9fSwia2V5dHlwZSI6IlBST0RVQ1RJT04iLCJwZXJtaXR0ZWRSZWZlcmVyIjoiIiwic3Vic2NyaWJlZEFQSXMiOlt7InN1YnNjcmliZXJUZW5hbnREb21haW4iOiJjYXJib24uc3VwZXIiLCJuYW1lIjoiU0lNUEVHLVRyZW5nZ2FsZWsiLCJjb250ZXh0IjoiXC9zaW1wZWdcLzEuMCIsInB1Ymxpc2hlciI6ImRpc2tvbWluZm9fdHJlbmdnYWxla2thYiIsInZlcnNpb24iOiIxLjAiLCJzdWJzY3JpcHRpb25UaWVyIjoiVW5saW1pdGVkIn1dLCJ0b2tlbl90eXBlIjoiYXBpS2V5IiwicGVybWl0dGVkSVAiOiIiLCJpYXQiOjE3Mzk3NjQ2MjYsImp0aSI6Ijk2MDZhNWQ3LWE3NjgtNGFhMy04ZDRhLWVkZGNkZjc3MWU2MCJ9.M50Z3dV0mKAEswsFPqIO3bbllwDhhpTH12nCjVTvkM__V3rA1EHRzyGE_WC7Rzx1Po61xlZllM0LUin7-pYYjKNBZ04nJe7tiEQ2yeM8eRbrB9cKZojbGwdTym21XA6DxUUbp1OCOp3BKA_oNo78bQJGyVVC--r5q9ib4T-pwC2ztwJqnYYA64glndIJn1cl4jqAj26jY_E7DHMRl6xY-32iYwMsCXy23FlG43UQMU77KW3c2zQ9um1N5v_DPSRLCbS8hbmkcMNq_r3F9pheUdVXKGXoBWeSkhVE86TEohGTNDM7LiA2WhcpMKgHBwy1ds3OTq-eOFKQ1Ia-X9Sw0g=='),
    ],

];
