<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ClaudeService
{
    private string $apiKey;
    private string $haikuModel;
    private string $sonnetModel;
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey      = config('claude.api_key');
        $this->haikuModel  = config('claude.haiku_model');
        $this->sonnetModel = config('claude.sonnet_model');
    }

    public function classify(string $message): string
    {
        $system = <<<'PROMPT'
Classify the seller's request into exactly one category. Reply with only the category label — no explanation, no punctuation.

Categories:
- product_content     → writing product titles, descriptions, keywords, hashtags
- marketing_campaign  → creating ad copy, campaigns, promotional content
- analysis_report     → analysing campaign data, performance summaries, trends
- unknown             → anything else

Examples:
"Write a title for my running shoes" → product_content
"Create a Facebook ad for our sale"  → marketing_campaign
"How did my last campaign perform?"  → analysis_report
"What is the weather?"               → unknown
PROMPT;

        $result = $this->call($system, $message, $this->haikuModel);
        $label  = strtolower(trim($result['content']));

        return in_array($label, ['product_content', 'marketing_campaign', 'analysis_report'])
            ? $label
            : 'unknown';
    }

    public function generate(
        string $systemPrompt,
        string $contextBlock,
        string $userMessage,
        string $model
    ): array {
        $userContent = $contextBlock
            ? "Context:\n{$contextBlock}\n\nRequest: {$userMessage}"
            : $userMessage;

        return $this->call($systemPrompt, $userContent, $model);
    }

    private function call(string $system, string $userMessage, string $model): array
    {
        $delays   = [1, 2];
        $attempts = 0;

        while (true) {
            try {
                $response = Http::withHeaders([
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])->timeout(30)->post(self::API_URL, [
                    'model'      => $model,
                    'max_tokens' => 1024,
                    'system'     => $system,
                    'messages'   => [['role' => 'user', 'content' => $userMessage]],
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    return [
                        'content'       => $body['content'][0]['text'] ?? '',
                        'input_tokens'  => $body['usage']['input_tokens'] ?? 0,
                        'output_tokens' => $body['usage']['output_tokens'] ?? 0,
                    ];
                }

                throw new \RuntimeException(
                    "Claude API error {$response->status()}: {$response->body()}"
                );

            } catch (ConnectionException $e) {
                if ($attempts >= count($delays)) {
                    throw new \RuntimeException(
                        "Claude API unreachable after 3 attempts: {$e->getMessage()}"
                    );
                }
                sleep($delays[$attempts]);
                $attempts++;
            }
        }
    }
}
