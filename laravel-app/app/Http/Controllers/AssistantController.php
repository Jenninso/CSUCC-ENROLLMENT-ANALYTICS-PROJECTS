<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function chat(Request $request, AssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'page' => ['nullable', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:24'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.text' => ['nullable', 'string', 'max:2000'],
            'history.*.content' => ['nullable', 'string', 'max:2000'],
        ]);

        $sessionKey = 'assistant_history';
        $history = $validated['history'] ?? session($sessionKey, []);

        $result = $assistant->reply(
            $validated['message'],
            $request->user(),
            $validated['page'] ?? null,
            is_array($history) ? $history : [],
        );

        $updated = array_values(array_filter([
            ...array_map(static fn ($row) => [
                'role' => ($row['role'] ?? '') === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($row['content'] ?? $row['text'] ?? ''),
            ], is_array($history) ? $history : []),
            ['role' => 'user', 'content' => $validated['message']],
            ['role' => 'assistant', 'content' => $result['reply']],
        ], static fn ($row) => trim($row['content']) !== ''));

        session([$sessionKey => array_slice($updated, -16)]);

        return response()->json($result + [
            'llm' => $assistant->isLlmConfigured(),
        ]);
    }
}
