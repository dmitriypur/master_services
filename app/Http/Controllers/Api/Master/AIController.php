<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Master\ParseVoiceCommandRequest;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    public function parse(ParseVoiceCommandRequest $request, AIService $ai): JsonResponse
    {
        $data = $ai->parseBookingData((string) $request->input('text'));

        return response()->json($data);
    }
}
