# CyberLearn AI

A Laravel 13 cybersecurity education platform for academic research comparing a Single AI Agent learning condition with a Multi AI Agent learning condition.

## What is included

- Student registration, login, remember me, forgot password request, logout, and session handling.
- Role-based access for `student` and `admin`.
- Student dashboard, lesson library, lesson detail pages, sidebar lesson navigation, embedded approved YouTube videos, MCQ quizzes, timed quiz UI, and result pages.
- Single AI Agent mode with one cybersecurity tutor.
- Multi AI Agent mode with Navigation, Explanation, and Video Recommendation agents.
- OpenAI chat integration through `App\Services\AiAgentService` with a local fallback when `OPENAI_API_KEY` is not configured.
- Admin dashboard for users, lessons, quizzes, research analytics, quiz performance, and AI usage metrics.
- Relational schema for roles, users, sections, lessons, videos, quizzes, questions, answers, quiz results, AI interactions, progress tracking, and bookmarks.

## Demo accounts

After seeding:

- Admin: `admin@cyberlearn.test` / `password`
- Student: `student@cyberlearn.test` / `password`

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

## OpenAI configuration

```env
OPENAI_API_KEY=your_key_here
OPENAI_MODEL=gpt-4o-mini
```

AI requests are rate limited on the browser endpoint with `throttle:20,1`. The service records prompt, response, agent type, platform version, latency, tokens, user, and lesson for research analytics.

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

Use HTTPS, configure a real mail driver for password reset emails, set `APP_ENV=production`, `APP_DEBUG=false`, and use MySQL or another managed relational database.
