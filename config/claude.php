<?php

return [
    'api_key'      => env('CLAUDE_API_KEY', ''),
    'haiku_model'  => env('CLAUDE_HAIKU_MODEL', 'claude-haiku-4-5-20251001'),
    'sonnet_model' => env('CLAUDE_SONNET_MODEL', 'claude-sonnet-4-6'),
];
