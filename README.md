# CyberLearn AI

A Laravel 13 cybersecurity education platform for academic research comparing a Single AI Agent learning condition with a Multi AI Agent learning condition.

## What is included

- Student registration, login, remember me, forgot password request, logout, and session handling.
- Role-based access for `student` and `admin`.
- Student dashboard, lesson library, lesson detail pages, sidebar lesson navigation, embedded approved YouTube videos, MCQ quizzes, timed quiz UI, and result pages.
- Single AI Agent mode with one cybersecurity tutor.
- Multi AI Agent mode with Navigation, Explanation, and Video Recommendation agents.
- AI chat through `App\Services\AiAgentService`: Groq (primary, streamed), OpenAI as a secondary fallback, and built-in local responses when no provider is configured.
- Admin dashboard for users, lessons, quizzes, research analytics, quiz performance, and AI usage metrics.
- Relational schema for roles, users, sections, lessons, videos, quizzes, questions, answers, quiz results, AI interactions, progress tracking, and bookmarks.

## Demo accounts

After seeding:

- Admin: `admin@cyberlearn.test`
- Student: `student@cyberlearn.test`

Passwords come from `SEED_ADMIN_PASSWORD` / `SEED_STUDENT_PASSWORD`. In `local`/`testing` they default to `password`; in any other environment seeding aborts unless they are set.

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

## MySQL configuration

The project can run on SQLite for local demos. For MySQL, update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cyberlearn
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate:fresh --seed
```

## AI configuration

```env
GROQ_API_KEY=your_key_here          # used by both conditions unless overridden
GROQ_SINGLE_API_KEY=                # optional: key for the single-agent condition
GROQ_MULTI_API_KEY=                 # optional: key for the multi-agent condition
GROQ_MODEL=openai/gpt-oss-120b
OPENAI_API_KEY=                     # optional fallback
OPENAI_MODEL=gpt-4o-mini
```

AI requests are rate limited with `throttle:20,1` plus a per-user daily limit.

Groq's free tier allows about 8,000 tokens per minute for the whole account (single and multi share it); one AI answer costs roughly 800–1,500 tokens, so expect around 6 AI answers per minute before students get the built-in fallback replies. Short rate-limit waits (≤ 6 s) are retried once automatically. For a class, use a paid Groq tier and/or set `OPENAI_API_KEY` as an overflow fallback. Cached answers are per user and only for context-free questions. Assistant turns sent back in the chat history are only accepted if they match a reply the server actually sent. The service records prompt, response, agent type, platform version, latency, tokens, user, and lesson for research analytics.

## Research design

Both learning conditions use the same lessons, videos, and quizzes:

- Single condition: `single_tutor`
- Multi condition: `navigation`, `explanation`, `video`

Tracked metrics include lesson progress, time spent, quiz score, completion status, AI usage frequency, agent usage distribution, and recent quiz performance.

## Deployment notes

For production:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Also set `TRUSTED_PROXIES` to your load balancer/reverse proxy IPs (if any), and run `php artisan storage:link` so generated TTS audio is reachable. Text-to-speech in production needs `composer require aws/aws-sdk-php` plus AWS credentials (the macOS `say` fallback is for local development only).

Use HTTPS, configure a real mail driver for password reset emails, set `APP_ENV=production`, `APP_DEBUG=false`, and use MySQL or another managed relational database.
