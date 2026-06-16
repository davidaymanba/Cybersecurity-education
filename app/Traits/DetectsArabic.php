<?php

namespace App\Traits;

trait DetectsArabic
{
    /**
     * Returns true if the string contains at least one Arabic/Arabic-script character.
     */
    private function containsArabic(string $text): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }
}
