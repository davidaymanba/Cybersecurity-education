<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\VoiceController;
use App\Http\Middleware\SetLocale;
use App\Models\Lesson;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::middleware([SetLocale::class])->group(function () {

    Route::get('/', fn () => view('landing', [
        'lessons' => Schema::hasTable('lessons') ? Lesson::take(3)->get() : collect(),
    ]))->name('landing');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
        Route::get('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/register', [AuthController::class, 'store'])->name('register.store');
        Route::get('/forgot-password', [AuthController::class, 'forgot'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendReset'])->name('password.email');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
        Route::get('/lessons', [StudentController::class, 'lessons'])->name('lessons.index');
        Route::get('/versions/{version}/lessons/{lesson:slug}', [StudentController::class, 'lesson'])
            ->whereIn('version', ['single', 'multi'])
            ->name('lessons.show');
        Route::get('/quizzes/{quiz}', [StudentController::class, 'quiz'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}', [StudentController::class, 'submitQuiz'])->name('quizzes.submit');
        Route::get('/results/{result}', [StudentController::class, 'result'])->name('results.show');
        Route::post('/cyber-bot', AiChatController::class)->middleware('throttle:20,1')->name('cyber.bot');
        Route::post('/app-api/ai/chat', AiChatController::class)->middleware('throttle:20,1')->name('api.ai.chat');
        Route::post('/app-api/ai/chat/stream', [AiChatController::class, 'stream'])->middleware('throttle:20,1')->name('api.ai.chat.stream');
        Route::post('/app-api/progress', ProgressController::class)->name('api.progress');
    });

    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/lessons', [AdminController::class, 'lessons'])->name('lessons');
        Route::post('/lessons', [AdminController::class, 'storeLesson'])->name('lessons.store');
        Route::put('/lessons/{lesson}', [AdminController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [AdminController::class, 'deleteLesson'])->name('lessons.delete');
        Route::get('/quizzes', [AdminController::class, 'quizzes'])->name('quizzes');
        Route::post('/quizzes', [AdminController::class, 'storeQuiz'])->name('quizzes.store');
        Route::put('/quizzes/{quiz}', [AdminController::class, 'updateQuiz'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [AdminController::class, 'deleteQuiz'])->name('quizzes.delete');
        Route::post('/quizzes/{quiz}/questions', [AdminController::class, 'storeQuestion'])->name('questions.store');
        Route::put('/questions/{question}', [AdminController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/questions/{question}', [AdminController::class, 'deleteQuestion'])->name('questions.delete');
        Route::get('/app-api/analytics', AnalyticsController::class)->name('api.analytics');
    });

    // Simple voice assistant UI and API (client-side STT/TTS PoC)
    Route::get('/voice', [VoiceController::class, 'show'])->name('voice');
    Route::post('/voice/ai', [VoiceController::class, 'respond'])->name('voice.respond');
    // Allow GET for quick browser testing; prefer POST in production.
    Route::match(['get', 'post'], '/voice/tts', [VoiceController::class, 'tts'])->name('voice.tts');

    // Language switcher (stores locale in session)
    Route::get('lang/{lang}', function ($lang) {
        $available = ['en', 'ar'];
        if (in_array($lang, $available)) {
            session(['locale' => $lang]);
        }

        return redirect()->back();
    })->name('lang.switch');

});
