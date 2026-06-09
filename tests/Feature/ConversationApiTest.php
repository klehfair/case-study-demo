<?php

namespace Tests\Feature;

use App\Services\ClaudeService;
use App\Services\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedSeller(): void
    {
        \DB::table('sellers')->insert([
            'id'               => 1,
            'name'             => 'Test Seller',
            'tone_preference'  => 'professional',
            'product_category' => 'electronics',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function mockServices(string $intent, array $agentResponse): void
    {
        $this->mock(ClaudeService::class, function ($mock) use ($intent, $agentResponse) {
            $mock->shouldReceive('classify')->andReturn($intent);
            $mock->shouldReceive('generate')->andReturn([
                'content'       => json_encode($agentResponse),
                'input_tokens'  => 100,
                'output_tokens' => 50,
            ]);
        });

        $this->mock(MemoryService::class, function ($mock) {
            $mock->shouldReceive('load')->andReturn([
                'turns'   => [],
                'summary' => null,
                'seller'  => ['id' => 1, 'name' => 'Test Seller', 'tone_preference' => 'professional', 'product_category' => 'electronics'],
            ]);
            $mock->shouldReceive('save');
        });
    }

    public function test_send_routes_to_product_agent(): void
    {
        $this->seedSeller();
        $this->mockServices('product_content', [
            'title'       => 'Test Title',
            'description' => 'Test description here.',
            'hashtags'    => ['#test'],
            'keywords'    => ['test keyword'],
        ]);

        $response = $this->postJson('/api/conversation', [
            'seller_id'  => 1,
            'session_id' => 'test-session-001',
            'message'    => 'Write a product title for running shoes',
        ]);

        $response->assertOk()
            ->assertJsonPath('intent', 'product_content')
            ->assertJsonPath('agent', 'ProductAgent')
            ->assertJsonStructure(['session_id', 'intent', 'agent', 'model_used', 'response', 'tokens', 'retries']);
    }

    public function test_send_routes_to_marketing_agent(): void
    {
        $this->seedSeller();
        $this->mockServices('marketing_campaign', [
            'campaign_name' => 'Summer Sale',
            'ad_copy'       => 'Buy now.',
            'platform'      => 'Facebook',
            'cta'           => 'Shop Now',
        ]);

        $response = $this->postJson('/api/conversation', [
            'seller_id'  => 1,
            'session_id' => 'test-session-002',
            'message'    => 'Create a Facebook ad campaign',
        ]);

        $response->assertOk()
            ->assertJsonPath('intent', 'marketing_campaign')
            ->assertJsonPath('agent', 'MarketingAgent');
    }

    public function test_send_returns_clarification_for_unknown_intent(): void
    {
        $this->seedSeller();
        $this->mockServices('unknown', []);

        $response = $this->postJson('/api/conversation', [
            'seller_id'  => 1,
            'session_id' => 'test-session-003',
            'message'    => 'What is the weather today?',
        ]);

        $response->assertOk()
            ->assertJsonPath('intent', 'unknown')
            ->assertJsonPath('agent', null);
    }

    public function test_send_validates_required_fields(): void
    {
        $response = $this->postJson('/api/conversation', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['seller_id', 'session_id', 'message']);
    }

    public function test_send_rejects_nonexistent_seller(): void
    {
        $response = $this->postJson('/api/conversation', [
            'seller_id'  => 999,
            'session_id' => 'sess',
            'message'    => 'Hello',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['seller_id']);
    }

    public function test_history_returns_session_turns(): void
    {
        $this->seedSeller();
        \DB::table('conversations')->insert([
            'seller_id'  => 1,
            'session_id' => 'hist-001',
            'role'       => 'user',
            'content'    => 'Hello',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/conversation/hist-001');

        $response->assertOk()
            ->assertJsonPath('session_id', 'hist-001')
            ->assertJsonCount(1, 'turns');
    }

    public function test_clear_returns_success(): void
    {
        $this->mock(MemoryService::class, function ($mock) {
            $mock->shouldReceive('clear');
        });

        $response = $this->deleteJson('/api/conversation/sess-to-clear');

        $response->assertOk()
            ->assertJsonPath('cleared', true);
    }
}
