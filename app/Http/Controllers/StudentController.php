<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\ProgressTracking;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    /** Extra seconds allowed after the timer ends (network latency, slow submit). */
    private const QUIZ_GRACE_SECONDS = 30;

    public function dashboard(): View
    {
        $user = auth()->user();
        $lessons = Lesson::with('section')->where('is_published', true)->orderBy('sort_order')->get();
        $progressRows = $user->progress()->whereIn('lesson_id', $lessons->modelKeys())->get()->keyBy('lesson_id');
        $progress = $progressRows->map(fn ($row) => (int) $row->progress_percent);
        $recentResults = $user->quizResults()->with('quiz.lesson')->latest()->take(5)->get();

        // Continue with the lesson touched most recently that is not finished, else the next unstarted one.
        $inProgress = $progressRows->filter(fn ($row) => $row->progress_percent < 100)->sortByDesc('updated_at')->first();
        $continue = ($inProgress ? $lessons->firstWhere('id', $inProgress->lesson_id) : null)
            ?? $lessons->first(fn ($lesson) => ($progress[$lesson->id] ?? 0) < 100);

        $bookmarks = $user->bookmarks()->with('lesson.section')->latest()->get()
            ->pluck('lesson')->filter(fn ($lesson) => $lesson?->is_published)->take(4)->values();

        $stats = [
            'course_progress' => $lessons->isEmpty() ? 0 : (int) round($lessons->sum(fn ($lesson) => $progress[$lesson->id] ?? 0) / $lessons->count()),
            'completed' => $progressRows->filter(fn ($row) => $row->progress_percent >= 100)->count(),
            'total' => $lessons->count(),
            'attempts' => $user->quizResults()->count(),
            'best' => (int) $user->quizResults()->max('score'),
        ];

        return view('student.dashboard', compact('lessons', 'progress', 'recentResults', 'continue', 'bookmarks', 'stats'));
    }

    public function lessons(): View
    {
        $user = auth()->user();

        return view('student.lessons', [
            'sections' => $this->sectionsWithPublishedLessons(),
            'progress' => $user->progress()->pluck('progress_percent', 'lesson_id'),
            'bookmarked' => $user->bookmarks()->pluck('lesson_id')->flip(),
        ]);
    }

    public function lesson(string $version, Lesson $lesson): View
    {
        $this->ensureVisible($lesson);

        $lesson->load(['section', 'videos', 'quiz.questions.answers']);
        $sections = $this->sectionsWithPublishedLessons();
        $progress = ProgressTracking::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $lesson->id,
        ], ['started_at' => now(), 'progress_percent' => 10]);

        $bookmarked = auth()->user()->bookmarks()->where('lesson_id', $lesson->id)->exists();
        $completed = auth()->user()->progress()->where('progress_percent', '>=', 100)->pluck('lesson_id')->flip();

        return view('student.lesson', compact('lesson', 'sections', 'progress', 'version', 'bookmarked', 'completed'));
    }

    public function quiz(Request $request, Quiz $quiz): View
    {
        $quiz->load('lesson', 'questions.answers');
        $this->ensureVisible($quiz->lesson);

        // The server owns the clock: remember when this attempt started.
        $key = $this->quizSessionKey($quiz);
        if (! $request->session()->has($key)) {
            $request->session()->put($key, now()->timestamp);
        }

        $elapsed = now()->timestamp - (int) $request->session()->get($key);
        $remainingSeconds = max(0, $quiz->timer_seconds - $elapsed);

        // A stale attempt whose time ran out starts over with a fresh timer.
        if ($remainingSeconds === 0) {
            $request->session()->put($key, now()->timestamp);
            $remainingSeconds = $quiz->timer_seconds;
        }

        return view('student.quiz', compact('quiz', 'remainingSeconds'));
    }

    public function submitQuiz(Request $request, Quiz $quiz): RedirectResponse
    {
        $quiz->load('lesson', 'questions.answers');
        $this->ensureVisible($quiz->lesson);

        $key = $this->quizSessionKey($quiz);
        $startedAt = $request->session()->pull($key);

        if ($startedAt === null) {
            return redirect()->route('quizzes.show', $quiz)->with('status', __('messages.quiz_start_first'));
        }

        if (now()->timestamp - (int) $startedAt > $quiz->timer_seconds + self::QUIZ_GRACE_SECONDS) {
            return redirect()->route('quizzes.show', $quiz)->with('status', __('messages.quiz_time_expired'));
        }

        $submitted = (array) $request->input('answers', []);
        $correct = 0;
        $snapshot = [];

        foreach ($quiz->questions as $question) {
            $answerId = (int) ($submitted[$question->id] ?? 0);
            $answer = $question->answers->firstWhere('id', $answerId);
            $isCorrect = (bool) $answer?->is_correct;
            $correct += $isCorrect ? 1 : 0;
            $snapshot[] = [
                'question' => $question->question,
                'selected_answer_id' => $answerId,
                'correct' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        $total = max($quiz->questions->count(), 1);
        $score = (int) round(($correct / $total) * 100);
        $result = QuizResult::create([
            'user_id' => auth()->id(),
            'quiz_id' => $quiz->id,
            'score' => $score,
            'correct_answers' => $correct,
            'total_questions' => $total,
            'answers_snapshot' => $snapshot,
            'completed_at' => now(),
        ]);

        // A lesson only counts as completed when the student passes its quiz.
        if ($score >= $quiz->passing_score) {
            $progress = ProgressTracking::firstOrNew(['user_id' => auth()->id(), 'lesson_id' => $quiz->lesson_id]);
            $progress->started_at ??= now();
            $progress->progress_percent = 100;
            $progress->completed_at ??= now();
            $progress->save();
        }

        return redirect()->route('results.show', $result);
    }

    public function result(QuizResult $result): View
    {
        abort_unless($result->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $result->load('quiz.lesson');

        return view('student.results', compact('result'));
    }

    private function ensureVisible(?Lesson $lesson): void
    {
        abort_unless($lesson && ($lesson->is_published || auth()->user()->isAdmin()), 404);
    }

    private function sectionsWithPublishedLessons()
    {
        return Section::with(['lessons' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('sort_order')
            ->get();
    }

    private function quizSessionKey(Quiz $quiz): string
    {
        return "quiz_started.{$quiz->id}";
    }
}
