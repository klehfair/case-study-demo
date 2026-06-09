<?php

namespace App\Services;

use App\Services\Agents\AnalysisAgentService;
use App\Services\Agents\MarketingAgentService;
use App\Services\Agents\ProductAgentService;
use App\Services\Exceptions\OutputValidatorException;

class OrchestratorService
{
    public function __construct(
        private ClaudeService         $claude,
        private ProductAgentService   $productAgent,
        private MarketingAgentService $marketingAgent,
        private AnalysisAgentService  $analysisAgent,
        private OutputValidator       $validator,
    ) {}

    /**
     * Classify intent → route to agent → validate output → retry up to 2× → return structured result.
     */
    public function dispatch(string $message, array $context): array
    {
        $intent = $this->claude->classify($message);

        if ($intent === 'unknown') {
            return [
                'intent'     => 'unknown',
                'agent'      => null,
                'model_used' => config('claude.haiku_model'),
                'response'   => ['message' => 'I can help with product content, marketing campaigns, or campaign analysis. Could you clarify what you need?'],
                'tokens'     => ['input' => 0, 'output' => 0],
                'retries'    => 0,
            ];
        }

        [$agent, $agentName] = match ($intent) {
            'product_content'    => [$this->productAgent,   'ProductAgent'],
            'marketing_campaign' => [$this->marketingAgent, 'MarketingAgent'],
            'analysis_report'    => [$this->analysisAgent,  'AnalysisAgent'],
        };

        $retries       = 0;
        $lastException = null;

        while ($retries <= 2) {
            try {
                $raw = $agent->handle($message, $context);

                $this->validator->validate($intent, $raw);

                $meta = $raw['_meta'] ?? [];
                unset($raw['_meta']);

                return [
                    'intent'     => $intent,
                    'agent'      => $agentName,
                    'model_used' => $meta['model'] ?? '',
                    'response'   => $raw,
                    'tokens'     => [
                        'input'  => $meta['input_tokens']  ?? 0,
                        'output' => $meta['output_tokens'] ?? 0,
                    ],
                    'retries' => $retries,
                ];
            } catch (OutputValidatorException $e) {
                $lastException = $e;
                $retries++;
            } catch (\Throwable $e) {
                return [
                    'intent'     => $intent,
                    'agent'      => $agentName,
                    'model_used' => '',
                    'response'   => [
                        'error'  => 'An unexpected error occurred. Please try again.',
                        'detail' => $e->getMessage(),
                    ],
                    'tokens'  => ['input' => 0, 'output' => 0],
                    'retries' => $retries,
                ];
            }
        }

        // Structured fallback — never return a blank response (Slide 15)
        return [
            'intent'     => $intent,
            'agent'      => $agentName,
            'model_used' => '',
            'response'   => [
                'error'  => 'Output validation failed after 2 retries. Please rephrase your request.',
                'detail' => $lastException?->getMessage(),
            ],
            'tokens'  => ['input' => 0, 'output' => 0],
            'retries' => $retries,
        ];
    }
}
