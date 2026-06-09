<?php

namespace App\Services;

use App\Services\Exceptions\OutputValidatorException;

class OutputValidator
{
    private const SCHEMAS = [
        'product_content'    => ['title', 'description', 'hashtags', 'keywords'],
        'marketing_campaign' => ['campaign_name', 'ad_copy', 'platform', 'cta'],
        'analysis_report'    => ['summary', 'top_insight', 'recommended_action'],
    ];

    public function validate(string $intent, array $data): void
    {
        $required = self::SCHEMAS[$intent] ?? [];
        $missing  = array_values(array_filter($required, fn($field) => empty($data[$field])));

        if (!empty($missing)) {
            throw new OutputValidatorException($intent, $missing);
        }
    }
}
