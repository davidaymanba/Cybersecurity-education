<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PromptLoader
{
    /**
     * @var array<string, string>
     */
    private array $cache = [];

    /**
     * @var array<string, string>
     */
    private const PROMPTS = [
        'core.policy' => 'core_system_policy.md',
        'core.behavior' => 'base_agent_behavior.md',
        'agent.single_tutor' => 'agents/single_tutor.md',
        'agent.navigation' => 'agents/navigation.md',
        'agent.explanation' => 'agents/explanation.md',
        'agent.video' => 'agents/video.md',
    ];

    public function get(string $key): string
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $relativePath = self::PROMPTS[$key] ?? null;

        if (! $relativePath) {
            throw new RuntimeException("Unknown AI prompt key [{$key}].");
        }

        $path = resource_path('prompts/'.$relativePath);

        if (! File::exists($path)) {
            throw new RuntimeException("AI prompt file is missing [{$relativePath}].");
        }

        $prompt = trim((string) File::get($path));

        if ($prompt === '') {
            throw new RuntimeException("AI prompt file is empty [{$relativePath}].");
        }

        return $this->cache[$key] = $prompt;
    }
}
