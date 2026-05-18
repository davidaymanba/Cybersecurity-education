<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\VideoResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['label' => 'Student']);

        User::firstOrCreate(['email' => 'admin@cyberlearn.test'], [
            'role_id' => $adminRole->id,
            'name' => 'Research Admin',
            'password' => 'password',
        ]);

        User::firstOrCreate(['email' => 'student@cyberlearn.test'], [
            'role_id' => $studentRole->id,
            'name' => 'Demo Student',
            'password' => 'password',
        ]);

        $content = [
            ['Security Foundations', 'Core language for understanding modern cyber risk.', [
                ['CIA Triad and Risk', 'Learn confidentiality, integrity, availability, and how controls reduce business risk.', 'Beginner'],
                ['Authentication and Access Control', 'Compare passwords, MFA, least privilege, RBAC, and session security.', 'Beginner'],
            ]],
            ['Defensive Operations', 'Practical defensive thinking for monitoring, response, and recovery.', [
                ['Phishing Detection', 'Identify social engineering patterns and design safer reporting habits.', 'Beginner'],
                ['Incident Response Lifecycle', 'Move through preparation, identification, containment, eradication, recovery, and lessons learned.', 'Intermediate'],
            ]],
            ['Secure Systems', 'Security-minded development and infrastructure fundamentals.', [
                ['Web Application Security', 'Understand input validation, access checks, CSRF, XSS, and SQL injection defenses.', 'Intermediate'],
                ['Network Security Basics', 'Explore segmentation, firewalls, IDS, VPNs, and secure network design.', 'Intermediate'],
            ]],
        ];

        foreach ($content as $sectionIndex => [$sectionTitle, $description, $lessons]) {
            $section = Section::firstOrCreate(
                ['slug' => Str::slug($sectionTitle)],
                ['title' => $sectionTitle, 'description' => $description, 'sort_order' => $sectionIndex + 1]
            );

            foreach ($lessons as $lessonIndex => [$title, $summary, $difficulty]) {
                $lesson = Lesson::firstOrCreate(
                    ['slug' => Str::slug($title)],
                    [
                        'section_id' => $section->id,
                        'title' => $title,
                        'category' => 'Cybersecurity',
                        'summary' => $summary,
                        'difficulty' => $difficulty,
                        'duration_minutes' => 22 + ($lessonIndex * 6),
                        'sort_order' => ($sectionIndex * 10) + $lessonIndex + 1,
                        'content' => $this->lessonBody($summary),
                        'code_examples' => ["// Security checklist example\nvalidateInput();\nenforceLeastPrivilege();\nlogSecurityEvent();"],
                    ]
                );

                VideoResource::firstOrCreate(
                    ['lesson_id' => $lesson->id, 'youtube_id' => 'inWWhr5tnEA'],
                    [
                        'title' => 'Cybersecurity fundamentals overview',
                        'channel_name' => 'IBM Technology',
                        'channel_id' => 'UC4xKdmAXFh4ACyhpiQ_3qBw',
                        'thumbnail_url' => 'https://img.youtube.com/vi/inWWhr5tnEA/hqdefault.jpg',
                        'description' => 'Approved educational video aligned to foundational cybersecurity topics.',
                        'approved' => true,
                    ]
                );

                $quiz = Quiz::firstOrCreate(
                    ['lesson_id' => $lesson->id],
                    ['title' => $title.' Knowledge Check', 'timer_seconds' => 600, 'passing_score' => 70]
                );

                if ($quiz->questions()->count() === 0) {
                    $this->addQuestion($quiz, 'What is the best first step when learning this topic?', [
                        'Memorize tool names only',
                        'Understand the risk, control, and real-world example',
                        'Ignore context',
                        'Disable all systems',
                    ], 1, 'Security learning is strongest when concepts are connected to risk and practical controls.');
                    $this->addQuestion($quiz, 'Which behavior supports safer cybersecurity practice?', [
                        'Sharing passwords for convenience',
                        'Skipping updates indefinitely',
                        'Using least privilege and reporting suspicious activity',
                        'Turning off logs',
                    ], 2, 'Least privilege, monitoring, updates, and reporting reduce exposure.');
                }
            }
        }
    }

    private function lessonBody(string $summary): string
    {
        return '<h2>Learning goals</h2><p>'.$summary.'</p><ul><li>Define the core concept in plain language.</li><li>Recognize common risks and warning signs.</li><li>Match risks to practical defensive controls.</li></ul><h2>Applied example</h2><p>Imagine a student portal handling grades, assignments, and login sessions. A security decision should protect student data, preserve trust, and maintain availability during normal academic use.</p><h2>Reflection</h2><p>Ask the AI assistant to explain this lesson with an example from a university environment, then complete the quiz to record your progress.</p>';
    }

    private function addQuestion(Quiz $quiz, string $questionText, array $answers, int $correctIndex, string $explanation): void
    {
        $question = Question::create(['quiz_id' => $quiz->id, 'question' => $questionText, 'explanation' => $explanation]);

        foreach ($answers as $index => $answer) {
            Answer::create(['question_id' => $question->id, 'answer' => $answer, 'is_correct' => $index === $correctIndex]);
        }
    }
}
