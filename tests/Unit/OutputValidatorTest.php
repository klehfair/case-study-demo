<?php

namespace Tests\Unit;

use App\Services\Exceptions\OutputValidatorException;
use App\Services\OutputValidator;
use Tests\TestCase;

class OutputValidatorTest extends TestCase
{
    private OutputValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new OutputValidator();
    }

    public function test_product_content_passes_with_all_fields(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate('product_content', [
            'title'       => 'Blue Shoes',
            'description' => 'Great shoes.',
            'hashtags'    => ['#shoes'],
            'keywords'    => ['shoes'],
        ]);
    }

    public function test_product_content_throws_when_title_missing(): void
    {
        $this->expectException(OutputValidatorException::class);
        $this->expectExceptionMessageMatches('/title/');

        $this->validator->validate('product_content', [
            'description' => 'Great shoes.',
            'hashtags'    => ['#shoes'],
            'keywords'    => ['shoes'],
        ]);
    }

    public function test_marketing_campaign_passes_with_all_fields(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate('marketing_campaign', [
            'campaign_name' => 'Summer Sale',
            'ad_copy'       => 'Buy now.',
            'platform'      => 'Facebook',
            'cta'           => 'Shop Now',
        ]);
    }

    public function test_marketing_campaign_throws_when_cta_missing(): void
    {
        $this->expectException(OutputValidatorException::class);
        $this->expectExceptionMessageMatches('/cta/');

        $this->validator->validate('marketing_campaign', [
            'campaign_name' => 'Summer Sale',
            'ad_copy'       => 'Buy now.',
            'platform'      => 'Facebook',
        ]);
    }

    public function test_analysis_report_passes_with_all_fields(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate('analysis_report', [
            'summary'            => 'Good performance.',
            'top_insight'        => 'CTR was high.',
            'recommended_action' => 'Increase budget.',
        ]);
    }

    public function test_analysis_report_throws_when_recommended_action_missing(): void
    {
        $this->expectException(OutputValidatorException::class);
        $this->expectExceptionMessageMatches('/recommended_action/');

        $this->validator->validate('analysis_report', [
            'summary'     => 'Good performance.',
            'top_insight' => 'CTR was high.',
        ]);
    }
}
