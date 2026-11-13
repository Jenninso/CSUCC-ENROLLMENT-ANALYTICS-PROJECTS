<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Floating assistant LLM
    |--------------------------------------------------------------------------
    | provider: gemini | openai | local
    | Prefer gemini (free API key from https://aistudio.google.com/apikey).
    | Set ASSISTANT_PROVIDER=local to force offline keyword chat.
    */
    'provider' => env('ASSISTANT_PROVIDER', 'gemini'),

    'gemini' => [
        // Prefer ASSISTANT_GEMINI_API_KEY so a machine-wide OPENAI/GEMINI key is not picked up by mistake.
        'api_key' => env('ASSISTANT_GEMINI_API_KEY', env('GEMINI_API_KEY')),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
    ],

    'openai' => [
        'api_key' => env('ASSISTANT_OPENAI_API_KEY'),
        'model' => env('ASSISTANT_OPENAI_MODEL', env('OPENAI_MODEL', 'qwen/qwen3.8-27b')),
        'base_url' => env('ASSISTANT_OPENAI_BASE_URL', env('OPENAI_BASE_URL', 'https://api.openai.com/v1')),
    ],

    'max_history' => (int) env('ASSISTANT_MAX_HISTORY', 6),
    'timeout' => (int) env('ASSISTANT_TIMEOUT', 25),
    'max_tokens' => (int) env('ASSISTANT_MAX_TOKENS', 900),
];
