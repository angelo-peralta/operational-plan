<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'microsoft' => [
        'tenant_id' => env('AZURE_AD_TENANT_ID'),
        'client_id' => env('AZURE_AD_CLIENT_ID'),
        'client_secret' => env('AZURE_AD_CLIENT_SECRET'),
        'redirect_uri' => env('AZURE_AD_REDIRECT_URI'),
        'authority' => env('AZURE_AD_AUTHORITY', 'https://login.microsoftonline.com'),
        'graph_url' => env('MICROSOFT_GRAPH_URL', 'https://graph.microsoft.com/v1.0'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
