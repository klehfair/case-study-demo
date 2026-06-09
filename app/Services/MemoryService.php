<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MemoryService
{
    private const TTL          = 1800;
    private const MAX_TURNS    = 8;
    private const SUMMARISE_AT = 6;

    public function __construct(private ClaudeService $claude) {}

    /**
     * Tier read: Valkey (last N turns) → MySQL summary fallback.
     * Returns ['turns' => [...], 'summary' => string|null, 'seller' => [...]]
     */
    public function load(string $sessionId, int $sellerId): array
    {
        $seller = DB::table('sellers')->find($sellerId);
        $turns  = $this->fetchTurns($sessionId);
        $summary = null;

        if (empty($turns)) {
            $row     = DB::table('memory_store')
                ->where('seller_id', $sellerId)
                ->where('session_id', $sessionId)
                ->first();
            $summary = $row?->summary;
        }

        return [
            'turns'   => $turns,
            'summary' => $summary,
            'seller'  => $seller ? (array) $seller : [],
        ];
    }

    /**
     * Write one turn to Valkey list + persist to conversations table.
     */
    public function save(string $sessionId, int $sellerId, array $turn): void
    {
        $key = $this->key($sessionId);

        Redis::rpush($key, json_encode($turn));
        Redis::expire($key, self::TTL);
        Redis::ltrim($key, -self::MAX_TURNS, -1);
        $len = Redis::llen($key);

        DB::table('conversations')->insert([
            'seller_id'     => $sellerId,
            'session_id'    => $sessionId,
            'role'          => $turn['role'],
            'content'       => is_array($turn['content']) ? json_encode($turn['content']) : $turn['content'],
            'agent_used'    => $turn['agent_used']    ?? null,
            'model_used'    => $turn['model_used']    ?? null,
            'input_tokens'  => $turn['input_tokens']  ?? null,
            'output_tokens' => $turn['output_tokens'] ?? null,
            'created_at'    => now(),
        ]);

        if ($len >= self::SUMMARISE_AT) {
            $this->summarise($sessionId, $sellerId);
        }
    }

    /**
     * Delete Valkey key — clears the in-flight session cache.
     */
    public function clear(string $sessionId): void
    {
        Redis::del($this->key($sessionId));
    }

    /**
     * Compress old turns into a short summary via Haiku, persist to memory_store.
     */
    public function summarise(string $sessionId, int $sellerId): void
    {
        $turns = $this->fetchTurns($sessionId);
        if (count($turns) < 2) {
            return;
        }

        $history = collect($turns)
            ->map(function ($t) {
                $content = is_string($t['content']) ? $t['content'] : json_encode($t['content']);
                return strtoupper($t['role']) . ': ' . $content;
            })
            ->implode("\n");

        $system = 'You are a conversation summariser. Compress the conversation below into 1-2 sentences capturing key context and decisions. Reply with the summary only — no labels, no quotes.';

        $result = $this->claude->generate($system, '', $history, config('claude.haiku_model'));

        DB::table('memory_store')->upsert(
            [
                'seller_id'  => $sellerId,
                'session_id' => $sessionId,
                'summary'    => $result['content'],
                'created_at' => now(),
            ],
            ['seller_id', 'session_id'],
            ['summary', 'created_at']
        );
    }

    private function fetchTurns(string $sessionId): array
    {
        $raw = Redis::lrange($this->key($sessionId), 0, -1);
        return array_map(fn($item) => json_decode($item, true), $raw);
    }

    private function key(string $sessionId): string
    {
        return "bw:session:{$sessionId}:turns";
    }
}
