<?php

namespace App\Services\Agents;

use App\Services\ClaudeService;

class AnalysisAgentService
{
    public function __construct(private ClaudeService $claude) {}

    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a campaign analytics specialist. Summarise performance data and provide actionable recommendations.

Always respond with valid JSON only — no prose, no markdown fences, no explanation outside the JSON.

Output format:
{
  "summary":            "2-3 sentence performance overview",
  "top_insight":        "single most important finding",
  "recommended_action": "one concrete next step"
}

Example input:  "Analyse our last Facebook campaign — 5000 impressions, 150 clicks, 12 conversions"
Example output:
{
  "summary": "The campaign achieved a 3% CTR and 8% conversion rate from clicks — both above industry averages for e-commerce. Retargeting audiences drove the majority of conversions.",
  "top_insight": "Retargeting audiences converted at 3x the rate of cold audiences.",
  "recommended_action": "Increase retargeting budget by 20% and reduce cold audience spend in the next campaign."
}
PROMPT;
    }

    public function handle(string $message, array $context): array
    {
        $result  = $this->claude->generate(
            $this->getSystemPrompt(),
            $this->buildContextBlock($context),
            $message,
            config('claude.haiku_model')
        );

        $decoded = json_decode($result['content'], true) ?? [];

        return array_merge($decoded, [
            '_meta' => [
                'model'         => config('claude.haiku_model'),
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
            $parts[] = "Seller: {$s['name']}. Category: {$s['product_category']}.";
        }

        if (!empty($context['summary'])) {
            $parts[] = "Session context: {$context['summary']}";
        }

        return implode("\n", $parts);
    }
}
