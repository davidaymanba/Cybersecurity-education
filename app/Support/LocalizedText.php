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

    public static function html(?string $value): string
    {
        return self::get($value);
    }
}
