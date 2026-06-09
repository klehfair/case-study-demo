<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConversationRequest;
use App\Services\MemoryService;
use App\Services\OrchestratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function __construct(
        private MemoryService       $memory,
        private OrchestratorService $orchestrator,
    ) {}

    public function send(ConversationRequest $request): JsonResponse
    {
        $sellerId  = $request->integer('seller_id');
        $sessionId = $request->string('session_id')->toString();
        $message   = $request->string('message')->toString();

        $context = $this->memory->load($sessionId, $sellerId);

        $this->memory->save($sessionId, $sellerId, [
            'role'    => 'user',
            'content' => $message,
        ]);

        $result = $this->orchestrator->dispatch($message, $context);

        $this->memory->save($sessionId, $sellerId, [
            'role'          => 'assistant',
            'content'       => $result['response'],
            'agent_used'    => $result['agent'],
            'model_used'    => $result['model_used'],
            'input_tokens'  => $result['tokens']['input'],
            'output_tokens' => $result['tokens']['output'],
        ]);

        return response()->json(array_merge(
            [
                'session_id'    => $sessionId,
                'memory_loaded' => !empty($context['turns']) || !empty($context['summary']),
            ],
            $result
        ));
    }

    public function history(string $sessionId): JsonResponse
    {
        $turns = DB::table('conversations')
            ->where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['role', 'content', 'agent_used', 'model_used', 'input_tokens', 'output_tokens', 'created_at']);

        return response()->json(['session_id' => $sessionId, 'turns' => $turns]);
    }

    public function clear(string $sessionId): JsonResponse
    {
        $this->memory->clear($sessionId);

        return response()->json(['session_id' => $sessionId, 'cleared' => true]);
    }
}
