<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Return all bookmarked lessons for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->with('lesson:id,title,slug,difficulty,duration_minutes')
            ->latest()
            ->get()
            ->map(fn ($b) => $b->lesson)
            ->filter();

        return response()->json($bookmarks->values());
    }
}
