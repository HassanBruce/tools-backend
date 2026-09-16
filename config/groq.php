<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Groq API keys
    |--------------------------------------------------------------------------
    |
    | Every request rotates across ALL of these keys x all of the models
    | below, so one key/model hitting Groq's free-tier rate limit doesn't
    | block the request -- it just moves to the next combination. Get extra
    | free keys at console.groq.com/keys (a single account can usually issue
    | several). Only GROQ_API_KEY_1 is required.
    |
    */

    'keys' => array_values(array_filter([
        env('GROQ_API_KEY_1'),
        env('GROQ_API_KEY_2'),
        env('GROQ_API_KEY_3'),
        env('GROQ_API_KEY_4'),
    ])),

    /*
    |--------------------------------------------------------------------------
    | Models to rotate across
    |--------------------------------------------------------------------------
    |
    | Comma-separated in .env. Check console.groq.com/docs/models for the
    | current catalog -- if one of these is ever decommissioned, the pool
    | just skips it and tries the next combination.
    |
    */

    'models' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'GROQ_MODELS',
            'llama-3.3-70b-versatile,llama-3.1-8b-instant,gemma2-9b-it,deepseek-r1-distill-llama-70b,qwen/qwen3-32b'
        ))
    ))),

    'cooldown_seconds' => (int) env('GROQ_COOLDOWN_SECONDS', 60),

    'timeout' => (int) env('GROQ_TIMEOUT', 30),

    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),

];
