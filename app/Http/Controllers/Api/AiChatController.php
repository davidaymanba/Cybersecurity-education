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
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'agent_type' => ['nullable', 'in:single_tutor,navigation,explanation,video'],
            'platform_version' => ['nullable', 'in:single,multi'],
        ]);

        $data['agent_type'] ??= 'single_tutor';
        $data['platform_version'] ??= 'single';

        $lesson = isset($data['lesson_id']) ? Lesson::find($data['lesson_id']) : null;

        return response()->json($agents->respond(
            $request->user(),
            $lesson,
            $data['message'],
            $data['agent_type'],
            $data['platform_version'],
            $data['history'] ?? []
        ));
    }
}
