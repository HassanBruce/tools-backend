<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadsFinder\GenerateSearchQueriesRequest;
use App\Services\LeadsFinder\SearchQueryGeneratorService;
use Illuminate\Http\JsonResponse;

class LeadsFinderController extends Controller
{
    public function searchQueries(GenerateSearchQueriesRequest $request, SearchQueryGeneratorService $service): JsonResponse
    {
        $niche = $request->string('niche')->trim()->toString();
        $pitch = $request->string('pitch')->trim()->toString();

        $queries = $service->generate($niche, $pitch);

        return response()->json([
            'niche' => $niche,
            'pitch' => $pitch,
            'queries' => $queries,
        ]);
    }
}
