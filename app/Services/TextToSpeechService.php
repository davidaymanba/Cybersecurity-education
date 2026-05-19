<?php

namespace App\Services;

use Aws\Polly\PollyClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TextToSpeechService
{
    /**
     * Generate speech audio for the given text.
     * Returns array with keys: path, url, full_path
     */
    public function generate(string $text, ?string $voice = null, string $format = 'mp3'): array
    {
        $dir = 'public/audio';
        Storage::makeDirectory($dir);
        $baseName = Str::slug(Str::limit($text, 30)) ?: Str::random(8);
        $filename = $baseName . '-' . time() . '.' . $format;
        $path = $dir . '/' . $filename;
        $fullPath = storage_path('app/' . $path);

        // Use AWS Polly when credentials present
        if (env('AWS_ACCESS_KEY_ID') && env('AWS_SECRET_ACCESS_KEY')) {
            $client = new PollyClient([
                'version' => '2016-06-10',
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            $voiceId = $voice ?: (str_starts_with(app()->getLocale(), 'ar') ? 'Zeina' : 'Joanna');
            $result = $client->synthesizeSpeech([
                'OutputFormat' => strtoupper($format),
                'Text' => $text,
                'VoiceId' => $voiceId,
                'TextType' => 'text',
            ]);

            $audio = $result->get('AudioStream')->getContents();
            file_put_contents($fullPath, $audio);
        } else {
            // Fallback for local macOS development using `say`
            if (defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Darwin' : PHP_OS === 'Darwin') {
                $aiffPath = preg_replace('/\.[^\.]+$/', '', $fullPath) . '.aiff';
                $cmd = 'say -o ' . escapeshellarg($aiffPath) . ' --data-format=LEF32@44100 ' . escapeshellarg($text);
                @shell_exec($cmd);

                // try to convert to mp3 if requested and ffmpeg is available
                $ffmpeg = @shell_exec('command -v ffmpeg');
                if ($format === 'mp3' && $ffmpeg) {
                    $cmd2 = 'ffmpeg -y -i ' . escapeshellarg($aiffPath) . ' -acodec libmp3lame ' . escapeshellarg($fullPath) . ' 2>&1';
                    @shell_exec($cmd2);
                    @unlink($aiffPath);
                } else {
                    // keep aiff file
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.aiff';
                    $path = $dir . '/' . $filename;
                    $fullPath = $aiffPath;
                }
            } else {
                throw new \RuntimeException('No TTS provider configured and no local TTS available.');
            }
        }

        return [
            'path' => $path,
            'url' => url('storage/audio/' . basename($path)),
            'full_path' => $fullPath,
        ];
    }
}
