<?php

declare(strict_types=1);

namespace Leadscaptain\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Leadscaptain\Application\DTOs\LeadData;
use Leadscaptain\Application\UseCases\FetchAllLeadsUseCase;
use Leadscaptain\Domain\Repositories\LeadRepositoryInterface;

final class LeadSyncController extends Controller
{
    public function index(): JsonResponse
    {
        $leads = DB::table('leads')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($leads, 200);
    }

    public function triggerSync(FetchAllLeadsUseCase $useCase): JsonResponse
    {
        $useCase->execute();

        return response()->json([
            'status' => 'success',
            'message' => 'Sync queued successfully on [leads] queue.',
        ], 202);
    }

    public function handleWebhook(Request $request, LeadRepositoryInterface $repository): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string'],
            'data' => ['required', 'array'],
        ]);

        $id = $validated['data']['key'] ?? $validated['data']['id'] ?? $validated['data']['leadscaptain_public_identifier'] ?? null;

        if (empty($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The data payload must contain a valid key, id, or leadscaptain_public_identifier.',
            ], 422);
        }

        $leadDto = LeadData::fromArray($validated['data']);
        $repository->upsertBatch([$leadDto->toDomain()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead processed and upserted successfully.',
        ], 200);
    }
}
