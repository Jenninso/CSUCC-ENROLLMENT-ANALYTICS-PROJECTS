<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AssistantService
{
    /**
     * Multi-turn chat reply with optional LLM and permission-aware links.
     *
     * @param  list<array{role?: string, text?: string, content?: string}>  $history
     * @return array{reply: string, links: list<array{label: string, url: string}>, mode: string}
     */
    public function reply(string $message, ?User $user = null, ?string $page = null, array $history = []): array
    {
        $message = trim($message);
        if ($message === '') {
            return $this->pack(
                'What would you like help with? You can ask about students, enrollment, analytics, forecasting, accounts, or settings.',
                $this->linksFor($user, ['dashboard', 'students.index', 'enrollment.index']),
                'local'
            );
        }

        $history = $this->normalizeHistory($history);

        if ($this->canAnswerLocally($message, $history)) {
            $local = $this->localConversation($message, $user, $page, $history);

            return $this->pack($local['reply'], $local['links'], 'local');
        }

        $provider = $this->resolvedProvider();

        if ($provider === 'gemini') {
            $llm = $this->chatGemini($message, $user, $page, $history);
            if ($llm !== null) {
                return $this->pack($llm, $this->suggestLinks($message.' '.$llm, $user), 'gemini');
            }
        }

        if ($provider === 'openai') {
            $llm = $this->chatOpenAi($message, $user, $page, $history);
            if ($llm !== null) {
                return $this->pack($llm, $this->suggestLinks($message.' '.$llm, $user), 'openai');
            }
        }

        // Local conversational fallback (still multi-turn aware)
        $local = $this->localConversation($message, $user, $page, $history);

        return $this->pack($local['reply'], $local['links'], 'local');
    }

    public function isLlmConfigured(): bool
    {
        return $this->resolvedProvider() !== 'local';
    }

    private function resolvedProvider(): string
    {
        $provider = strtolower((string) config('assistant.provider', 'gemini'));

        if ($provider === 'local') {
            return 'local';
        }

        if ($provider === 'openai' && filled(config('assistant.openai.api_key'))) {
            return 'openai';
        }

        if (filled(config('assistant.gemini.api_key'))) {
            return 'gemini';
        }

        if (filled(config('assistant.openai.api_key'))) {
            return 'openai';
        }

        return 'local';
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function chatGemini(string $message, ?User $user, ?string $page, array $history): ?string
    {
        $key = config('assistant.gemini.api_key');
        $model = config('assistant.gemini.model', 'gemini-2.0-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $contents = [];
        foreach (array_slice($history, -((int) config('assistant.max_history', 16))) as $turn) {
            $contents[] = [
                'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['content']]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        try {
            $payload = [
                'systemInstruction' => [
                    'parts' => [['text' => $this->systemPrompt($user, $page)]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => (int) config('assistant.max_tokens', 280),
                ],
            ];

            $response = $this->httpClient()
                ->withQueryParameters(['key' => $key])
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Assistant Gemini error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('Assistant Gemini exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function chatOpenAi(string $message, ?User $user, ?string $page, array $history): ?string
    {
        $key = config('assistant.openai.api_key');
        $base = rtrim((string) config('assistant.openai.base_url'), '/');
        $model = config('assistant.openai.model', 'gpt-4o-mini');

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($user, $page)],
        ];
        foreach (array_slice($history, -((int) config('assistant.max_history', 16))) as $turn) {
            $messages[] = [
                'role' => $turn['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $turn['content'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = $this->httpClient()
                ->withToken($key)
                ->post($base.'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.4,
                    'max_tokens' => (int) config('assistant.max_tokens', 280),
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::warning('Assistant OpenAI error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $text = data_get($response->json(), 'choices.0.message.content');

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('Assistant OpenAI exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::connectTimeout(4)
            ->timeout((int) config('assistant.timeout', 12))
            ->acceptJson();

        // Local XAMPP often lacks a CA bundle; skip verify in local only.
        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function systemPrompt(?User $user, ?string $page): string
    {
        $name = $user?->first_name ?: 'there';
        $role = $user?->role?->role_name ?: 'staff';
        $page = $page ?: 'unknown';

        return <<<PROMPT
You are the friendly in-app assistant for CSUCC Enrollment Analytics (Caraga State University Cabadbaran Campus — CEIT Program enrollment decision support).

Speak like a helpful coworker in a natural conversation. Remember earlier messages. Ask a short clarifying question when needed. Keep answers concise (about 2–6 short sentences or a short bullet list). Do not invent features that are not listed below. Never claim you can change data yourself — guide the user to the right screen. This system supports decisions; it does not make administrative decisions automatically.

User: {$name} ({$role}). Current page path: {$page}.

What the app can do:
- Dashboard / Executive: enrollment KPIs
- Student Records: one record per student per term by Student ID (no personal names). Filters: year, semester, college, course, year level, section, gender, enrollment/scholarship/tuition status, risk. Actions dropdown: View / Edit / Delete. Add Student, Import CSV/Excel (.xlsx), & Export CSV when permitted. CEIT Program courses include Information Technology, Computer Engineering, and Electrical Engineering.
- Enrollment: aggregated counts; CSV import (template → preview → confirm); data quality; export
- Analytics: summary, trends, programs, colleges, retention
- Forecasting: project future enrollment from history
- AI Decision Support: generate reviewable insights; what-if (recruitment, retention, capacity); accept/reject insights
- Alerts, Reports
- Data Management: academic years, semesters, colleges, departments, programs
- Users / Approvals / Privileges / Delete requests / Login logs (role-based)
- Profile & Appearance (theme); System Settings; login OTP; forgot password via Employee ID → questions → email OTP

If the user greets you, greet back warmly and offer help. Follow up on their last topic when they say things like "yes", "how?", "then what?", or "show me".
PROMPT;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, links: list<array{label: string, url: string}>}
     */
    private function localConversation(string $message, ?User $user, ?string $page, array $history): array
    {
        $text = Str::lower($message);
        $prior = $this->lastAssistantTopic($history);
        $followUp = (bool) preg_match('/^(yes|yeah|yep|ok|okay|sure|how\??|then what|and then|continue|tell me more|show me|please|go on)\b/i', trim($message));

        if (preg_match('/^(hi|hello|hey|good\s+(morning|afternoon|evening))\b/i', $text)) {
            $name = $user?->first_name ?: 'there';

            return [
                'reply' => "Hi {$name}! I'm your CSUCC assistant. We can chat about anything in this system — students, enrollment, analytics, forecasting, AI insights, accounts, or settings. What are you trying to do right now?",
                'links' => $this->linksFor($user, ['dashboard', 'students.index', 'ai-support.index']),
            ];
        }

        $matched = $this->matchTopic($text);
        if (! $matched && $followUp && $prior) {
            $matched = $prior;
        }

        if ($matched) {
            $reply = $this->conversationalize($matched['answer'], $message, $history, $page);

            return [
                'reply' => $reply,
                'links' => $this->linksFor($user, $matched['routes'] ?? []),
            ];
        }

        $hint = $this->pageHint($page);

        return [
            'reply' => "I'm with you — could you tell me a bit more? For example: adding a student, importing enrollment CSV, reading a forecast, or changing your theme."
                .($hint ? "\n\n".$hint : '')
                ."\n\n(Tip: for fuller natural AI chat, set ASSISTANT_GEMINI_API_KEY in .env — free from Google AI Studio.)",
            'links' => $this->linksFor($user, ['students.index', 'enrollment.index', 'analytics.summary', 'profile.edit']),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function conversationalize(string $answer, string $message, array $history, ?string $page): string
    {
        $openers = [
            'Got it — ',
            'Sure — ',
            'Here’s how that works: ',
            'Happy to help with that. ',
        ];
        $opener = $openers[crc32($message) % count($openers)];

        if ($history !== []) {
            $opener = 'Continuing from what we were discussing — ';
        }

        $out = $opener.trim($answer);
        $hint = $this->pageHint($page);
        if ($hint) {
            $out .= "\n\n".$hint;
        }
        $out .= "\n\nWant me to walk you through the next step?";

        return $out;
    }

    /** @return array{keywords: list<string>, answer: string, routes?: list<string>}|null */
    private function matchTopic(string $text): ?array
    {
        foreach ($this->topics() as $topic) {
            foreach ($topic['keywords'] as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $topic;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{keywords: list<string>, answer: string, routes?: list<string>}|null
     */
    private function lastAssistantTopic(array $history): ?array
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') !== 'assistant') {
                continue;
            }
            $matched = $this->matchTopic(Str::lower($history[$i]['content']));
            if ($matched) {
                return $matched;
            }
        }

        return null;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function canAnswerLocally(string $message, array $history): bool
    {
        $text = Str::lower(trim($message));
        if (preg_match('/^(hi|hello|hey|good\s+(morning|afternoon|evening))\b/i', $text)) {
            return true;
        }
        if ($this->matchTopic($text) !== null) {
            return true;
        }

        $followUp = (bool) preg_match('/^(yes|yeah|yep|ok|okay|sure|how\??|then what|and then|continue|tell me more|show me|please|go on)\b/i', trim($message));

        return $followUp && $this->lastAssistantTopic($history) !== null;
    }

    /** @return list<array{keywords: list<string>, answer: string, routes?: list<string>}> */
    private function topics(): array
    {
        return [
            [
                'keywords' => ['student', 'add a student', 'create a student', 'edit a student', 'delete a student'],
                'answer' => "Student Records keep one row per student per term (Student ID only — no personal names).\n\n• Open Student Records to filter by year, college, course, risk, and more.\n• Use Add Student if you have enrollment manage access.\n• Import File accepts CSV or Excel (.xlsx): download the template → upload → preview → confirm.\n• Export downloads the filtered CSV.\n• Row Actions → View / Edit / Delete.\n\nCEIT Program courses include Information Technology, Computer Engineering, and Electrical Engineering.",
                'routes' => ['students.index', 'students.create', 'students.import', 'students.export'],
            ],
            [
                'keywords' => ['enrollment', 'import', 'csv', 'template', 'data quality', 'upload'],
                'answer' => "Enrollment counts come from the student records for the same year, semester, course, and year level.\n\n• View or export the counts from Enrollment.\n• Add or import students under Student Records. Saving a student updates the matching enrollment count.\n• Data Quality flags gaps and inconsistencies.",
                'routes' => ['enrollment.index', 'enrollment.quality', 'students.import'],
            ],
            [
                'keywords' => ['analytic', 'trend', 'retention', 'program analytic', 'college analytic', 'dashboard'],
                'answer' => "Analytics summarize verified enrollment data:\n• Summary dashboard\n• Trends over academic years\n• Program & college breakdowns\n• Retention views\n\nThe main Dashboard and Executive view give quick KPIs.",
                'routes' => ['dashboard', 'executive', 'analytics.summary', 'analytics.trends', 'analytics.programs', 'analytics.colleges', 'analytics.retention'],
            ],
            [
                'keywords' => ['forecast', 'predict', 'projection'],
                'answer' => 'Forecasting projects future enrollment from historical patterns. Open Forecasting, review assumptions, then generate or refresh a forecast. Treat results as decision support — not automatic decisions.',
                'routes' => ['forecasting.index'],
            ],
            [
                'keywords' => ['ai support', 'insight', 'what-if', 'what if', 'decision support', 'scenario'],
                'answer' => "AI Decision Support builds reviewable insights from analytics and offers what-if scenarios (recruitment, retention, capacity).\n\nGenerate insights, open one to review, then Accept or Reject.",
                'routes' => ['ai-support.index', 'ai-support.what-if'],
            ],
            [
                'keywords' => ['alert', 'notification', 'warning'],
                'answer' => 'Alerts highlight unusual enrollment or data issues. Open Alerts to review items, mark them read, or regenerate from current analytics.',
                'routes' => ['alerts.index'],
            ],
            [
                'keywords' => ['report', 'pdf', 'generate report'],
                'answer' => 'Reports builds shareable summaries from current analytics. Use Export on Students/Enrollment for raw CSV.',
                'routes' => ['reports.index', 'students.export', 'enrollment.export'],
            ],
            [
                'keywords' => ['academic year', 'semester', 'college', 'department', 'program', 'course', 'data management', 'bsit', 'bscpe', 'bsee'],
                'answer' => 'Data Management maintains Academic Years, Semesters, Colleges, Departments, and Programs. Add future years under Academic Years so they appear in filters.',
                'routes' => [
                    'data-management.academic-years.index',
                    'data-management.semesters.index',
                    'data-management.colleges.index',
                    'data-management.departments.index',
                    'data-management.programs.index',
                ],
            ],
            [
                'keywords' => ['user', 'account', 'privilege', 'approve', 'block', 'delete request', 'registration', 'admin'],
                'answer' => "Account tools (by role): Users, Approvals, Privileges, Delete requests, and Login logs. Ask an admin if you cannot see a menu.",
                'routes' => ['users.index', 'approvals.index', 'delete-requests.index', 'login-logs.index'],
            ],
            [
                'keywords' => ['password', 'forgot', 'otp', 'secret question', 'login', 'sign in', 'credentials'],
                'answer' => "Sign-in uses Employee ID/username and password, with OTP when required. Forgot password: Employee ID → security questions → email OTP → new password. Profile & Appearance is in the top-right menu.",
                'routes' => ['profile.edit', 'password.request', 'login'],
            ],
            [
                'keywords' => ['setting', 'theme', 'dark mode', 'appearance', 'profile', 'avatar'],
                'answer' => 'Profile & Appearance lets you change theme color, background, and dark/light mode. System Settings (if permitted) covers campus-wide options such as mail.',
                'routes' => ['profile.edit', 'settings.index'],
            ],
            [
                'keywords' => ['risk', 'gpa', 'attendance', 'scholarship', 'tuition', 'filter'],
                'answer' => 'On Student Records, use the filter bar for academic details and risk level. Risk badges summarize GPA, grades, attendance, and failed subjects.',
                'routes' => ['students.index'],
            ],
            [
                'keywords' => ['export', 'download'],
                'answer' => 'Export uses your current filters on Students or Enrollment. Import a file from Student Records.',
                'routes' => ['students.export', 'students.import', 'students.template', 'enrollment.export'],
            ],
            [
                'keywords' => ['permission', 'access denied', 'cannot see', "can't see", 'unauthorized', 'forbidden'],
                'answer' => 'Menus depend on your role privileges. Ask an Administrator or Super Administrator to grant the right permission.',
                'routes' => ['profile.edit', 'users.index'],
            ],
            [
                'keywords' => ['ceit', 'cabadbaran', 'csucc', 'about', 'scope'],
                'answer' => 'This system supports CEIT Program enrollment planning at CSUCC Cabadbaran. It is decision support — people still make administrative decisions.',
                'routes' => ['about', 'dashboard', 'ai-support.index'],
            ],
        ];
    }

    private function pageHint(?string $page): ?string
    {
        if (! $page) {
            return null;
        }

        return match (true) {
            str_contains($page, '/students') => 'I see you’re on Student Records right now.',
            str_contains($page, '/enrollment') => 'I see you’re on Enrollment right now.',
            str_contains($page, '/analytics') => 'I see you’re in Analytics right now.',
            str_contains($page, '/forecasting') => 'I see you’re on Forecasting right now.',
            str_contains($page, '/ai-support') => 'I see you’re in AI Decision Support right now.',
            str_contains($page, '/alerts') => 'I see you’re on Alerts right now.',
            str_contains($page, '/reports') => 'I see you’re on Reports right now.',
            str_contains($page, '/users') => 'I see you’re in Account management right now.',
            str_contains($page, '/data-management') => 'I see you’re in Data Management right now.',
            default => null,
        };
    }

    /**
     * @param  list<array{role?: string, text?: string, content?: string}>  $history
     * @return list<array{role: string, content: string}>
     */
    private function normalizeHistory(array $history): array
    {
        $out = [];
        foreach ($history as $row) {
            if (! is_array($row)) {
                continue;
            }
            $role = ($row['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($row['content'] ?? $row['text'] ?? ''));
            if ($content === '') {
                continue;
            }
            $out[] = ['role' => $role, 'content' => Str::limit($content, 500, '…')];
        }

        return array_slice($out, -((int) config('assistant.max_history', 16)));
    }

    /** @return list<array{label: string, url: string}> */
    private function suggestLinks(string $haystack, ?User $user): array
    {
        $text = Str::lower($haystack);
        $matched = $this->matchTopic($text);
        $routes = $matched['routes'] ?? ['dashboard', 'students.index', 'enrollment.index', 'ai-support.index'];

        return $this->linksFor($user, $routes);
    }

    /**
     * @param  list<string>  $routeNames
     * @return list<array{label: string, url: string}>
     */
    private function linksFor(?User $user, array $routeNames): array
    {
        $labels = [
            'dashboard' => 'Dashboard',
            'executive' => 'Executive view',
            'students.index' => 'Student Records',
            'students.create' => 'Add Student',
            'students.export' => 'Export Students',
            'enrollment.index' => 'Enrollment',
            'enrollment.create' => 'Add Enrollment',
            'students.import' => 'Import Students',
            'students.template' => 'Student CSV Template',
            'enrollment.quality' => 'Data Quality',
            'enrollment.export' => 'Export Enrollment',
            'analytics.summary' => 'Analytics Summary',
            'analytics.trends' => 'Trends',
            'analytics.programs' => 'Program Analytics',
            'analytics.colleges' => 'College Analytics',
            'analytics.retention' => 'Retention',
            'forecasting.index' => 'Forecasting',
            'ai-support.index' => 'AI Decision Support',
            'ai-support.what-if' => 'What-if Analysis',
            'alerts.index' => 'Alerts',
            'reports.index' => 'Reports',
            'data-management.academic-years.index' => 'Academic Years',
            'data-management.semesters.index' => 'Semesters',
            'data-management.colleges.index' => 'Colleges',
            'data-management.departments.index' => 'Departments',
            'data-management.programs.index' => 'Programs',
            'users.index' => 'Users',
            'approvals.index' => 'Approvals',
            'delete-requests.index' => 'Delete Requests',
            'login-logs.index' => 'Login Logs',
            'profile.edit' => 'Profile & Appearance',
            'settings.index' => 'System Settings',
            'password.request' => 'Forgot Password',
            'login' => 'Sign in',
            'about' => 'About',
        ];

        $permissionMap = [
            'students.index' => 'enrollment.view',
            'students.create' => 'enrollment.manage',
            'students.import' => 'enrollment.manage',
            'students.template' => 'enrollment.manage',
            'students.export' => 'enrollment.view',
            'enrollment.index' => 'enrollment.view',
            'enrollment.create' => 'enrollment.manage',
            'enrollment.quality' => 'enrollment.view',
            'enrollment.export' => 'enrollment.view',
            'analytics.summary' => 'analytics.view',
            'analytics.trends' => 'analytics.view',
            'analytics.programs' => 'analytics.view',
            'analytics.colleges' => 'analytics.view',
            'analytics.retention' => 'analytics.view',
            'forecasting.index' => 'forecasting.view',
            'ai-support.index' => 'ai.view',
            'ai-support.what-if' => 'ai.view',
            'alerts.index' => 'alerts.view',
            'reports.index' => 'reports.view',
            'data-management.academic-years.index' => 'data.manage',
            'data-management.semesters.index' => 'data.manage',
            'data-management.colleges.index' => 'data.manage',
            'data-management.departments.index' => 'data.manage',
            'data-management.programs.index' => 'data.manage',
            'users.index' => 'users.manage',
            'approvals.index' => 'users.approve',
            'login-logs.index' => 'logs.view',
            'settings.index' => 'settings.view',
        ];

        $out = [];
        foreach ($routeNames as $name) {
            if (! isset($labels[$name]) || ! Route::has($name)) {
                continue;
            }
            if ($name === 'delete-requests.index' && $user && ! $user->isSuperAdmin()) {
                continue;
            }
            $perm = $permissionMap[$name] ?? null;
            if ($perm && $user && ! $user->canAccess($perm)) {
                continue;
            }
            if ($user && in_array($name, ['password.request', 'login'], true)) {
                continue;
            }
            try {
                $out[] = ['label' => $labels[$name], 'url' => route($name)];
            } catch (\Throwable) {
            }
        }

        return array_slice($out, 0, 6);
    }

    /**
     * @param  list<array{label: string, url: string}>  $links
     * @return array{reply: string, links: list<array{label: string, url: string}>, mode: string}
     */
    private function pack(string $reply, array $links, string $mode): array
    {
        return ['reply' => $reply, 'links' => $links, 'mode' => $mode];
    }
}
