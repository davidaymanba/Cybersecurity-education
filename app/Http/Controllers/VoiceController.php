<?php

namespace App\Http\Controllers;

use App\Services\AiAgentService;
use App\Services\TextToSpeechService;
use App\Traits\DetectsArabic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoiceController extends Controller
{
    use DetectsArabic;

    public function show(): View
    {
        return view('voice', ['serverTts' => TextToSpeechService::isAvailable()]);
    }

    /**
     * Voice AI endpoint — requires authenticated user, no anonymous fallback.
     */
    public function respond(Request $request, AiAgentService $ai): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // Guard: authentication is required — never fall back to another user's account
        abort_unless($user !== null, 401, 'Authentication required.');

        $result = $ai->respond($user, null, $data['text'], 'single_tutor', 'single');

        return response()->json($result);
    }

    /**
     * TTS endpoint — requires POST (no GET in production).
     */
    public function tts(Request $request, TextToSpeechService $tts): JsonResponse
    {
        $data = $request->validate([
            'text'   => ['required', 'string', 'max:3000'],
            'voice'  => ['nullable', Rule::in(TextToSpeechService::ALLOWED_VOICES)],
            'format' => ['nullable', 'string', 'in:mp3,aiff'],
        ]);

        if (! TextToSpeechService::isAvailable()) {
            return response()->json(['ok' => false, 'error' => 'Server text-to-speech is not configured.'], 503);
        }

        try {
            $out = $tts->generate($data['text'], $data['voice'] ?? null, $data['format'] ?? 'mp3');

            return response()->json(['ok' => true, 'url' => $out['url']]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'TTS generation failed.'], 500);
        }
    }
}
