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
    public function dashboard(): View
    {
        $user = auth()->user();
        $lessons = Lesson::with('section')->where('is_published', true)->orderBy('sort_order')->get();
        $progress = $user->progress()->pluck('progress_percent', 'lesson_id');
        $recentResults = $user->quizResults()->with('quiz.lesson')->latest()->take(4)->get();

        return view('student.dashboard', compact('lessons', 'progress', 'recentResults'));
    }

    public function lessons(): View
    {
        return view('student.lessons', [
            'sections' => Section::with('lessons.progress')->orderBy('sort_order')->get(),
        ]);
    }

    public function lesson(string $version, Lesson $lesson): View
    {
        $lesson->load(['section', 'videos', 'quiz.questions.answers']);
        $sections = Section::with('lessons')->orderBy('sort_order')->get();
        $progress = ProgressTracking::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $lesson->id,
        ], ['started_at' => now(), 'progress_percent' => 10]);

        return view('student.lesson', compact('lesson', 'sections', 'progress', 'version'));
    }

    public function quiz(Quiz $quiz): View
    {
        $quiz->load('lesson', 'questions.answers');

        return view('student.quiz', compact('quiz'));
    }

    public function submitQuiz(Request $request, Quiz $quiz): RedirectResponse
    {
        $quiz->load('questions.answers');
        $submitted = $request->input('answers', []);
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

        ProgressTracking::updateOrCreate(
            ['user_id' => auth()->id(), 'lesson_id' => $quiz->lesson_id],
            ['progress_percent' => 100, 'completed_at' => now()]
        );

        return redirect()->route('results.show', $result);
    }

    public function result(QuizResult $result): View
    {
        abort_unless($result->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $result->load('quiz.lesson');

        return view('student.results', compact('result'));
    }
}
