<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __invoke(Request $request, AiAgentService $agents): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'agent_type' => ['required', 'in:single_tutor,navigation,explanation,video'],
            'platform_version' => ['required', 'in:single,multi'],
        ]);

        $lesson = isset($data['lesson_id']) ? Lesson::find($data['lesson_id']) : null;

        return response()->json($agents->respond(
            $request->user(),
            $lesson,
            $data['message'],
            $data['agent_type'],
            $data['platform_version']
        ));
    }
}
