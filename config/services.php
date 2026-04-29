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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'mistral' => [
        'api_key' => env('MISTRAL_API_KEY'),
        'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
        'model_large' => env('MISTRAL_MODEL_LARGE', 'mistral-large-latest'),
        'model_medium' => env('MISTRAL_MODEL_MEDIUM', 'mistral-medium-latest'),
        'timeout' => (int) env('MISTRAL_TIMEOUT', 30),
    ],

    'artemisia' => [
        'force_llm' => (bool) env('ARTEMISIA_FORCE_LLM', true),
        'llm_provider' => env('ARTEMISIA_LLM_PROVIDER', 'mistral'),
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'model_large' => env('OLLAMA_MODEL_LARGE', 'mistral'),
            'model_medium' => env('OLLAMA_MODEL_MEDIUM', 'mistral'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
        ],
    ],

];
