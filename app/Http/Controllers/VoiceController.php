<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiAgentService;
use App\Services\TextToSpeechService;
use App\Models\User;

class VoiceController extends Controller
{
    public function show()
    {
        return view('voice');
    }

    public function respond(Request $request, AiAgentService $ai)
    {
        $data = $request->validate([
            'text' => 'required|string',
        ]);

        $user = $request->user() ?? User::first();

        $result = $ai->respond($user, null, $data['text'], 'single_tutor', 'web');

        return response()->json($result);
    }

    public function tts(Request $request, TextToSpeechService $tts)
    {
        $data = $request->validate([
            'text' => 'required|string',
            'voice' => 'nullable|string',
            'format' => 'nullable|string|in:mp3,aiff',
        ]);

        $format = $data['format'] ?? 'mp3';
        try {
            $out = $tts->generate($data['text'], $data['voice'] ?? null, $format);
            return response()->json(['ok' => true, 'url' => $out['url'], 'path' => $out['path']]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
