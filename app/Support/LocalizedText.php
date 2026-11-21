<?php

namespace App\Support;

class LocalizedText
{
    public static function get(?string $value): string
    {
        if ($value === null || app()->getLocale() !== 'ar') {
            return (string) $value;
        }

        // Look the text up directly: most source strings contain "." which trans() would treat
        // as a nested-key separator, so sentences (lesson summaries) were never translated.
        $map = trans('messages.content_map');

        return is_array($map) && isset($map[$value]) ? $map[$value] : $value;
    }

    /**
     * Lesson HTML: an exact match first, otherwise translate each whole text node (">sentence<")
     * that the map knows. Partial strings are never replaced, so unknown content stays intact.
     */
    public static function html(?string $value): string
    {
        $exact = self::get($value);
        if ($exact !== (string) $value || app()->getLocale() !== 'ar') {
            return $exact;
        }

        $map = trans('messages.content_map');
        if (! is_array($map)) {
            return $exact;
        }

        return preg_replace_callback('/>([^<>]+)</u', function (array $match) use ($map) {
            $text = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));

            return isset($map[$text]) ? '>'.e($map[$text]).'<' : $match[0];
        }, (string) $value);
    }
}
