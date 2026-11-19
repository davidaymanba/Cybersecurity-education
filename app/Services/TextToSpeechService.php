<?php

namespace App\Services;

use Aws\Polly\PollyClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextToSpeechService
{
    /**
     * Generate speech audio for the given text.
     * Returns array with keys: path, url, full_path
     */
    /** Polly voices the app is allowed to request. */
    public const ALLOWED_VOICES = ['Joanna', 'Matthew', 'Amy', 'Brian', 'Zeina', 'Hala', 'Zayd'];

    /** Generated audio older than this is deleted so the disk does not fill up. */
    private const MAX_FILE_AGE_SECONDS = 3600;

    private const AUDIO_DIR = 'audio';

    public function generate(string $text, ?string $voice = null, string $format = 'mp3'): array
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory(self::AUDIO_DIR);
        $this->pruneOldFiles();

        $voice    = in_array($voice, self::ALLOWED_VOICES, true) ? $voice : null;
        $baseName = Str::slug(Str::limit($text, 30, '')) ?: 'tts';
        $filename = $baseName.'-'.Str::lower(Str::random(12)).'.'.$format;
        $path     = self::AUDIO_DIR.'/'.$filename;
        $fullPath = $disk->path($path);

        // Use AWS Polly when credentials are configured (always prefer config() over env()).
        $awsKey    = config('services.polly.key');
        $awsSecret = config('services.polly.secret');
        $awsRegion = config('services.polly.region', 'us-east-1');

        if ($awsKey && $awsSecret && class_exists(PollyClient::class)) {
            $client = new PollyClient([
                'version'     => '2016-06-10',
                'region'      => $awsRegion,
                'credentials' => [
                    'key'    => $awsKey,
                    'secret' => $awsSecret,
                ],
            ]);

            $voiceId = $voice ?: (str_starts_with(app()->getLocale(), 'ar') ? 'Zeina' : 'Joanna');
            $result  = $client->synthesizeSpeech([
                'OutputFormat' => strtoupper($format),
                'Text'         => $text,
                'VoiceId'      => $voiceId,
                'TextType'     => 'text',
            ]);

            file_put_contents($fullPath, $result->get('AudioStream')->getContents());

            return [
                'path'      => $path,
                'url'       => $disk->url($path),
                'full_path' => $fullPath,
            ];
        }

        // macOS development fallback using the built-in `say` command.
        if (PHP_OS_FAMILY === 'Darwin') {
            $aiffPath = preg_replace('/\.[^.]+$/', '', $fullPath).'.aiff';
            $cmd = 'say -o '.escapeshellarg($aiffPath).' --data-format=LEF32@44100 '.escapeshellarg($text);
            shell_exec($cmd);

            // Convert to MP3 if ffmpeg is available.
            if ($format === 'mp3' && trim((string) shell_exec('command -v ffmpeg')) !== '') {
                $cmd2 = 'ffmpeg -y -i '.escapeshellarg($aiffPath).' -acodec libmp3lame '.escapeshellarg($fullPath).' 2>&1';
                shell_exec($cmd2);
                @unlink($aiffPath);
            } else {
                $filename = pathinfo($filename, PATHINFO_FILENAME).'.aiff';
                $path     = self::AUDIO_DIR.'/'.$filename;
                $fullPath = $aiffPath;
            }

            return [
                'path'      => $path,
                'url'       => $disk->url($path),
                'full_path' => $fullPath,
            ];
        }

        throw new \RuntimeException('No TTS provider configured. Install aws/aws-sdk-php and set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY.');
    }

    private function pruneOldFiles(): void
    {
        $disk = Storage::disk('public');
        $cutoff = time() - self::MAX_FILE_AGE_SECONDS;

        foreach ($disk->files(self::AUDIO_DIR) as $file) {
            try {
                if ($disk->lastModified($file) < $cutoff) {
                    $disk->delete($file);
                }
            } catch (\Throwable) {
                // Another request may have removed it already.
            }
        }
    }
}
