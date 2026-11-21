<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\UserDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\VoiceController;
use App\Http\Middleware\SetLocale;
use App\Support\MarketingPage;
use Illuminate\Support\Facades\Route;

Route::middleware([SetLocale::class])->group(function () {

    Route::get('/', fn () => MarketingPage::landing())->name('landing');

    // The redesign was reviewed under /preview; keep old links working.
    Route::permanentRedirect('/preview', '/')->name('preview');
    Route::get('/preview/{page}', fn (string $page) => redirect()->route($page, status: 301))
        ->whereIn('page', ['login', 'register'])->name('preview.auth');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1')->name('login.store');
        Route::get('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/register', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
        Route::get('/forgot-password', [AuthController::class, 'forgot'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendReset'])->middleware('throttle:3,1')->name('password.email');
        Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
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
        Route::post('/app-api/progress', ProgressController::class)->middleware('throttle:60,1')->name('api.progress');
        Route::get('/app-api/dashboard', UserDashboardController::class)->name('api.dashboard');

        // Account (change own password)
        Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->middleware('throttle:5,1')->name('account.password');

        // Bookmarks
        Route::post('/bookmarks/{lesson}', [BookmarkController::class, 'toggle'])->name('bookmarks.toggle');
        Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');

        // Voice assistant (auth required — no anonymous use)
        Route::get('/voice', [VoiceController::class, 'show'])->name('voice');
        Route::post('/voice/ai', [VoiceController::class, 'respond'])->middleware('throttle:20,1')->name('voice.respond');
        Route::post('/voice/tts', [VoiceController::class, 'tts'])->middleware('throttle:10,1')->name('voice.tts');
    });

    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('users.reset-password');
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
        // Video management
        Route::get('/videos', [AdminController::class, 'videos'])->name('videos');
        Route::post('/videos', [AdminController::class, 'storeVideo'])->name('videos.store');
        Route::put('/videos/{video}', [AdminController::class, 'updateVideo'])->name('videos.update');
        Route::delete('/videos/{video}', [AdminController::class, 'deleteVideo'])->name('videos.delete');
        Route::get('/app-api/analytics', AnalyticsController::class)->name('api.analytics');
    });

    // Component gallery for design work. Only exists on a local machine, never in testing/production.
    if (app()->environment('local')) {
        Route::get('/_ui', fn () => view('dev.ui', [
            'paginator' => new \Illuminate\Pagination\LengthAwarePaginator(range(1, 12), 48, 12, (int) request('page', 2), ['path' => url('/_ui')]),
        ]))->name('dev.ui');
    }

    // Language switcher (stores locale in session)
    Route::get('lang/{lang}', function ($lang) {
        if (in_array($lang, SetLocale::SUPPORTED, true)) {
            session(['locale' => $lang]);
        }

        return redirect()->back();
    })->name('lang.switch');

});
