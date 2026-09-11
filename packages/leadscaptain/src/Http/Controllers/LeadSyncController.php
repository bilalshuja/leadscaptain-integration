<?php

declare(strict_types=1);

namespace Leadscaptain\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Leadscaptain\Application\DTOs\LeadData;
use Leadscaptain\Application\UseCases\FetchAllLeadsUseCase;
use Leadscaptain\Domain\Repositories\LeadRepositoryInterface;

final class LeadSyncController extends Controller
{
    
    public function triggerSync(FetchAllLeadsUseCase $useCase): JsonResponse
    {
        $useCase->execute();

        return response()->json([
            'status' => 'success',
            'message' => 'Sync queued successfully on [leads] queue.',
        ], 202);
    }

    
    public function handleWebhook(Request $request, LeadRepositoryInterface$repository): JsonResponse
    {
        $validated =$request->validate([
            'event' => ['required', 'string'],
            'data' => ['required', 'array'],
            'data.id' => ['required', 'string'],
            'data.email' => ['nullable', 'string'],
            'data.name' => ['nullable', 'string'],
            'data.metadata' => ['nullable', 'array'],
            'data.created_at' => ['nullable', 'string'],
        ]);

        $leadDto = LeadData::fromArray($validated['data']);
        $repository->upsertBatch([$leadDto->toDomain()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead processed and upserted successfully.',
        ], 200);
    }
}