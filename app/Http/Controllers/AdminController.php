<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(AnalyticsService $analytics): View
    {
        return view('admin.dashboard', ['analytics' => $analytics->overview()]);
    }

    public function users(): View
    {
        return view('admin.users', ['users' => User::with('role')->latest()->paginate(12)]);
    }

    public function lessons(): View
    {
        return view('admin.lessons', [
            'lessons' => Lesson::with('section', 'quiz')->orderBy('sort_order')->paginate(12),
            'sections' => Section::orderBy('sort_order')->get(),
        ]);
    }

    public function storeLesson(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'title' => ['required', 'max:160'],
            'summary' => ['required'],
            'content' => ['required'],
            'difficulty' => ['required', 'max:40'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        Lesson::create($data);

        return back()->with('status', 'Lesson created.');
    }

    public function updateLesson(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'max:160'],
            'summary' => ['required'],
            'content' => ['required'],
            'difficulty' => ['required', 'max:40'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $lesson->update($data);

        return back()->with('status', 'Lesson updated.');
    }

    public function deleteLesson(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return back()->with('status', 'Lesson deleted.');
    }

    public function quizzes(): View
    {
        return view('admin.quizzes', [
            'quizzes' => Quiz::with('lesson', 'questions.answers')->paginate(10),
            'lessons' => Lesson::orderBy('title')->get(),
        ]);
    }

    public function storeQuiz(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'title' => ['required', 'max:160'],
            'timer_seconds' => ['required', 'integer', 'min:60'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'question' => ['required'],
            'answers' => ['required', 'array', 'size:4'],
            'correct_index' => ['required', 'integer', 'between:0,3'],
        ]);

        $quiz = Quiz::firstOrCreate(
            ['lesson_id' => $data['lesson_id']],
            ['title' => $data['title'], 'timer_seconds' => $data['timer_seconds'], 'passing_score' => $data['passing_score']]
        );
        $question = Question::create(['quiz_id' => $quiz->id, 'question' => $data['question']]);
        foreach ($data['answers'] as $index => $answer) {
            Answer::create(['question_id' => $question->id, 'answer' => $answer, 'is_correct' => (int) $index === (int) $data['correct_index']]);
        }

        return back()->with('status', 'Quiz question saved.');
    }
}
