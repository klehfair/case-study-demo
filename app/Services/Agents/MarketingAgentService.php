<?php

namespace App\Services\Agents;

use App\Services\ClaudeService;

class MarketingAgentService
{
    public function __construct(private ClaudeService $claude) {}

    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a marketing campaign specialist for e-commerce sellers. Create targeted ad campaigns.

Always respond with valid JSON only — no prose, no markdown fences, no explanation outside the JSON.

Output format:
{
  "campaign_name": "memorable campaign name",
  "ad_copy":       "compelling ad text, 50-100 words",
  "platform":      "recommended platform: Facebook | Instagram | Google | TikTok",
  "cta":           "call-to-action button text, max 25 characters"
}

Example input:  "Create a summer sale campaign for sports equipment"
Example output:
{
  "campaign_name": "Summer Sprint Sale",
  "ad_copy": "Run faster, train harder — our biggest sports sale of the year is here! Up to 40% off on premium running gear, weights, and accessories. Limited stock, so grab your favourites before they are gone.",
  "platform": "Facebook",
  "cta": "Shop the Sale Now"
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
