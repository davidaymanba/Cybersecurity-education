<?php

namespace App\Support;

class LocalizedText
{
    public static function get(?string $value): string
    {
        if ($value === null || app()->getLocale() !== 'ar') {
            return (string) $value;
        }

        return trans('messages.content_map.'.$value) !== 'messages.content_map.'.$value
            ? trans('messages.content_map.'.$value)
            : $value;
    }

    public static function html(?string $value): string
    {
        return self::get($value);
    }
}
