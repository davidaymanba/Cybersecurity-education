# Architecture

## Layers

- Routes: `routes/web.php` contains session-backed browser routes and `routes/api.php` exposes REST-style JSON endpoints.
- Controllers: authentication, student learning flow, admin content management, and API controllers live under `app/Http/Controllers`.
- Services: `AiAgentService` orchestrates a tutoring turn (rate/daily limits, safety, deterministic routing, per-user cache, AI call, interaction logging). It delegates to `App\Services\Ai\*`:
  - `IntentDetector` — keyword-based intent, safety, language and level detection.
  - `QuizTutor` — in-chat practice quiz (question bank, grading, follow-ups).
  - `CannedResponses` — deterministic bilingual replies and provider-failure fallbacks.
  - `PromptBuilder` — system prompts plus student, lesson and history context.
  - `AiProviderClient` — Groq (incl. streaming) with OpenAI fallback.
  `AnalyticsService` aggregates research dashboard metrics.
- Models: Eloquent models define the core relationships for lessons, quizzes, progress, users, and AI interactions.
- Views: Blade templates in `resources/views` provide the responsive SaaS-style UI.

## Database

Primary tables:

- `roles`, `users`
- `sections`, `lessons`, `video_resources`
- `quizzes`, `questions`, `answers`, `quiz_results`
- `ai_interactions`, `progress_tracking`, `bookmarks`

## AI agents

Agent definitions are centralized in `AiAgentService::AGENTS`.

- `single_tutor`: general cybersecurity tutor.
- `navigation`: learning path and platform guidance.
- `explanation`: concept simplification and examples.
- `video`: approved video guidance only.

## Security

- Laravel CSRF protection on browser forms and fetch calls.
- Password hashing through Laravel casts.
- Role-based admin middleware.
- Request validation in controllers.
- AI chat rate limiting.
- Approved video resources are seeded and stored in the database.
- Login, registration and password-reset rate limiting; only proxies listed in `TRUSTED_PROXIES` are trusted.
- AI answer cache is per user and only for context-free questions; client-sent assistant history is verified against stored replies.
- Lesson HTML is sanitized with an allow-list (`App\Support\HtmlSanitizer`) on save and on render.
- Unpublished lessons/quizzes return 404 to students; lesson completion requires passing the quiz within its server-side time limit.
