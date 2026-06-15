<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\ShowroomAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowroomAssistantController extends Controller
{
    public function __invoke(Request $request, ShowroomAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:600'],
        ]);

        return response()->json(
            $assistant->ask($validated['message'])
        );
    }
}
