<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ArtemisIA\ArtemisIAChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ArtemisIAApiController extends Controller
{
    public function __construct(
        private readonly ArtemisIAChatService $chatService,
    ) {
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'sessionId' => ['nullable', 'string', 'max:120'],
            'scopeOverride' => ['nullable', 'string', 'in:selected_only,global'],
            'selectedRecords' => ['nullable', 'array'],
            'selectedRecords.*.id' => ['required_with:selectedRecords', 'string', 'max:512'],
            'selectedRecords.*.type' => ['required_with:selectedRecords', 'string', 'in:data-resource,heritage-entity'],
            'selectedRecords.*.title' => ['nullable', 'string', 'max:2000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:8000'],
        ]);

        try {
            $result = $this->chatService->chat(
                message: (string) $validated['message'],
                selectedRecords: $validated['selectedRecords'] ?? [],
                sessionId: $validated['sessionId'] ?? null,
                scopeOverride: $validated['scopeOverride'] ?? null,
                history: $validated['history'] ?? [],
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json($result);
    }
}

