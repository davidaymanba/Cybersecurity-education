<?php

namespace App\Support;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\VideoResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Builds the JSON payload for the marketing/auth React islands (resources/js/marketing).
 * Everything shown there comes from here: real routes, real counts, session errors/old input.
 */
class MarketingPage
{
    public static function landing(): View
    {
        $hasTables = Schema::hasTable('lessons');
        $t = fn ($value) => LocalizedText::get($value);

        $lessons = $hasTables
            ? Lesson::with('section')->where('is_published', true)->orderBy('sort_order')->take(6)->get()
            : collect();

        return self::view(__('messages.site_name'), [
            'page' => 'landing',
            'routes' => self::routes() + [
                'lessons' => route('lessons.index'),
                'dashboard' => route('dashboard'),
            ],
            'stats' => [
                'lessons' => $hasTables ? Lesson::where('is_published', true)->count() : 0,
                'quizzes' => $hasTables ? Quiz::count() : 0,
                'videos' => $hasTables ? VideoResource::where('approved', true)->count() : 0,
                'agents' => 4,
            ],
            'lessons' => $lessons->map(fn (Lesson $lesson) => [
                'title' => $t($lesson->title),
                'summary' => $t($lesson->summary),
                'difficulty' => $t($lesson->difficulty),
                'minutes' => $lesson->duration_minutes,
                'section' => $t($lesson->section?->title),
            ])->values(),
        ]);
    }

    /**
     * login | register | forgot | reset. Forms post to the real routes; validation errors and
     * old input (never passwords) come back through the session.
     */
    public static function auth(string $page, array $extra = []): View
    {
        $errors = session('errors');
        $titles = [
            'login' => __('messages.login'),
            'register' => __('messages.register'),
            'forgot' => __('messages.reset_password'),
            'reset' => __('messages.set_new_password'),
        ];

        return self::view($titles[$page] ?? __('messages.site_name'), [
            'page' => $page,
            'csrf' => csrf_token(),
            'status' => session('status'),
            'errors' => $errors ? $errors->getBag('default')->toArray() : (object) [],
            'old' => (object) collect(session()->getOldInput())
                ->except(['password', 'password_confirmation', 'current_password', '_token'])
                ->all(),
            'routes' => self::routes() + [
                'loginStore' => route('login.store'),
                'registerStore' => route('register.store'),
                'forgotStore' => route('password.email'),
                'resetStore' => route('password.update'),
            ],
        ] + $extra);
    }

    private static function routes(): array
    {
        return [
            'home' => route('landing'),
            'login' => route('login'),
            'register' => route('register'),
            'forgot' => route('password.request'),
            'langAr' => route('lang.switch', 'ar'),
            'langEn' => route('lang.switch', 'en'),
        ];
    }

    private static function view(string $title, array $payload): View
    {
        return view('marketing', [
            'title' => $title,
            'payload' => ['locale' => app()->getLocale(), 'auth' => auth()->check()] + $payload,
        ]);
    }
}
