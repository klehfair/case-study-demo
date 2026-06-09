<?php

namespace Tests\Unit;

use App\Services\ClaudeService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeServiceTest extends TestCase
{
    private function fakeClaudeResponse(string $text, int $inputTokens = 10, int $outputTokens = 5): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $text]],
                'usage'   => ['input_tokens' => $inputTokens, 'output_tokens' => $outputTokens],
            ], 200),
        ]);
    }

    public function test_classify_returns_product_content(): void
    {
        $this->fakeClaudeResponse('product_content');
        $service = new ClaudeService();
        $this->assertSame('product_content', $service->classify('write me a title'));
    }

    public function test_classify_returns_marketing_campaign(): void
    {
        $this->fakeClaudeResponse('marketing_campaign');
        $service = new ClaudeService();
        $this->assertSame('marketing_campaign', $service->classify('create a Facebook ad'));
    }

    public function test_classify_returns_analysis_report(): void
    {
        $this->fakeClaudeResponse('analysis_report');
        $service = new ClaudeService();
        $this->assertSame('analysis_report', $service->classify('how did my campaign do?'));
    }

    public function test_classify_returns_unknown_for_unrecognised_label(): void
    {
        $this->fakeClaudeResponse('something_else');
        $service = new ClaudeService();
        $this->assertSame('unknown', $service->classify('what is the weather?'));
    }

    public function test_generate_returns_content_and_token_counts(): void
    {
        $this->fakeClaudeResponse('{"title":"Test"}', 100, 20);
        $service = new ClaudeService();

        $result = $service->generate('You are helpful.', 'ctx', 'write a title', 'claude-haiku-4-5-20251001');

        $this->assertSame('{"title":"Test"}', $result['content']);
        $this->assertSame(100, $result['input_tokens']);
        $this->assertSame(20, $result['output_tokens']);
    }

    public function test_generate_throws_on_http_error_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response('Server Error', 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Claude API error/');

        $service = new ClaudeService();
        $service->generate('system', '', 'msg', 'claude-haiku-4-5-20251001');
    }
}
