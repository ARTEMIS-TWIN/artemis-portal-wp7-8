<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HeritageEntitySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeritageEntityApiController extends Controller
{
    public function __construct(
        private readonly HeritageEntitySearchService $heritageEntities,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json($this->heritageEntities->search($request));
    }

    public function aggregations(Request $request): JsonResponse
    {
        return response()->json($this->heritageEntities->getSearchAggregationData($request));
    }

    public function count(): JsonResponse
    {
        return response()->json($this->heritageEntities->getTotalCount());
    }

    public function show(string $id): JsonResponse
    {
        $entity = $this->heritageEntities->getEntity($id);

        if ($entity === null) {
            return response()->json(['message' => 'Heritage entity not found.'], 404);
        }

        return response()->json($entity);
    }
}
