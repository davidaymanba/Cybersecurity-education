# Architecture

## Layers

- Routes: `routes/web.php` contains session-backed browser routes and `routes/api.php` exposes REST-style JSON endpoints.
- Controllers: authentication, student learning flow, admin content management, and API controllers live under `app/Http/Controllers`.
- Services: `AiAgentService` owns prompts, OpenAI calls, fallback responses, and interaction logging. `AnalyticsService` aggregates research dashboard metrics.
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
