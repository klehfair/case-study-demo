<?php

namespace App\Services\Agents;

use App\Services\ClaudeService;

class ProductAgentService
{
    public function __construct(private ClaudeService $claude) {}

    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a product content specialist for an e-commerce platform. Generate SEO-optimised product content.

Always respond with valid JSON only — no prose, no markdown fences, no explanation outside the JSON.

Output format:
{
  "title":       "compelling product title, max 80 characters",
  "description": "SEO description, 100-200 words",
  "hashtags":    ["5 relevant hashtags with # prefix"],
  "keywords":    ["5 SEO keywords, no # prefix"]
}

Example input:  "Write content for Blue Running Shoes for casual runners"
Example output:
{
  "title": "CloudStep Pro — Lightweight Running Shoes for Daily Training",
  "description": "Engineered for comfort and performance, the CloudStep Pro features responsive cushioning and a breathable mesh upper perfect for daily runs. Ideal for casual runners seeking reliable footwear that transitions from track to street.",
  "hashtags": ["#running", "#runningshoes", "#fitness", "#casualrunner", "#sportswear"],
  "keywords": ["running shoes", "lightweight running", "casual runner shoes", "daily training shoes", "breathable sneakers"]
}
PROMPT;
    }

    public function handle(string $message, array $context): array
    {
        $result  = $this->claude->generate(
            $this->getSystemPrompt(),
            $this->buildContextBlock($context),
            $message,
            config('claude.sonnet_model')
        );

        $content = $result['content'];
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }
        $decoded = json_decode(trim($content), true) ?? [];

        return array_merge($decoded, [
            '_meta' => [
                'model'         => config('claude.sonnet_model'),
                'input_tokens'  => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ],
        ]);
    }

    private function buildContextBlock(array $context): string
    {
        $parts = [];

        if (!empty($context['seller'])) {
            $s       = $context['seller'];
            $parts[] = "Seller tone: {$s['tone_preference']}. Product category: {$s['product_category']}.";
        }

        if (!empty($context['summary'])) {
            $parts[] = "Session context: {$context['summary']}";
        }

        return implode("\n", $parts);
    }
}
