<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    /**
     * Toggle a bookmark for the authenticated user and given lesson.
     * Returns JSON: { bookmarked: bool, count: int }
     */
    public function toggle(Request $request, Lesson $lesson): JsonResponse
    {
        $userId = $request->user()->id;

        $existing = Bookmark::where('user_id', $userId)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            Bookmark::create(['user_id' => $userId, 'lesson_id' => $lesson->id]);
            $bookmarked = true;
        }

        return response()->json([
            'bookmarked' => $bookmarked,
            'lesson_id' => $lesson->id,
        ]);
    }

    /**
     * Saved lessons: an HTML page for browsers, JSON for API callers (Accept: application/json).
     */
    public function index(Request $request): JsonResponse|View
    {
        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->with('lesson.section')
            ->latest()
            ->get()
            ->map(fn ($b) => $b->lesson)
            ->filter(fn ($lesson) => $lesson && ($lesson->is_published || $request->user()->isAdmin()))
            ->values();

        if ($request->wantsJson()) {
            return response()->json($bookmarks->map->only(['id', 'title', 'slug', 'difficulty', 'duration_minutes'])->values());
        }

        return view('student.bookmarks', [
            'lessons' => $bookmarks,
            'progress' => $request->user()->progress()->pluck('progress_percent', 'lesson_id'),
        ]);
    }
}
