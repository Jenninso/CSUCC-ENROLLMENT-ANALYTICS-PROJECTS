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

        if (preg_match('/^(hi|hello|hey|good\s+(morning|afternoon|evening))\b/i', $message)) {
            $local = $this->localConversation($message, $user, $page, $history);

            return $this->pack($local['reply'], $local['links'], 'local');
        }

        $topic = $this->matchedTopic($message, $history);
        $guide = is_array($topic) ? (string) ($topic['answer'] ?? '') : '';
        $provider = $this->resolvedProvider();

        if ($provider === 'gemini') {
            $llm = $this->chatGemini($message, $user, $page, $history, $guide);
            if ($llm !== null) {
                return $this->pack($llm, $this->linksForReply($message, $llm, $user, $topic), 'gemini');
            }
        }

        if ($provider === 'openai') {
            $llm = $this->chatOpenAi($message, $user, $page, $history, $guide);
            if ($llm !== null) {
                return $this->pack($llm, $this->linksForReply($message, $llm, $user, $topic), 'openai');
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
    private function chatGemini(string $message, ?User $user, ?string $page, array $history, string $guide = ''): ?string
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

        return $this->attempt(function () use ($key, $url, $contents, $user, $page, $guide) {
            return $this->httpClient()
                ->withQueryParameters(['key' => $key])
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $this->systemPrompt($user, $page, $guide)]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => (int) config('assistant.max_tokens', 900),
                    ],
                ]);
        }, 'Assistant Gemini', 'candidates.0.content.parts.0.text');
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function chatOpenAi(string $message, ?User $user, ?string $page, array $history, string $guide = ''): ?string
    {
        $key = config('assistant.openai.api_key');
        $base = rtrim((string) config('assistant.openai.base_url'), '/');
        $model = config('assistant.openai.model', 'gpt-4o-mini');

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($user, $page, $guide)],
        ];
        foreach (array_slice($history, -((int) config('assistant.max_history', 16))) as $turn) {
            $messages[] = [
                'role' => $turn['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $turn['content'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        return $this->attempt(function () use ($key, $base, $model, $messages) {
            return $this->httpClient()
                ->withToken($key)
                ->post($base.'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.4,
                    'max_tokens' => (int) config('assistant.max_tokens', 900),
                    'messages' => $messages,
                ]);
        }, 'Assistant OpenAI', 'choices.0.message.content');
    }

    /**
     * Try the chat request twice when the connection drops, then return plain text.
     */
    private function attempt(callable $send, string $label, string $textPath): ?string
    {
        $lastError = null;

        for ($try = 0; $try < 2; $try++) {
            try {
                $response = $send();
                if (! $response->successful()) {
                    if ($response->serverError() || $response->status() === 429) {
                        $lastError = 'status '.$response->status();
                        continue;
                    }
                    Log::warning($label.' error', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);

                    return null;
                }

                $text = data_get($response->json(), $textPath);

                return is_string($text) && trim($text) !== '' ? $this->plainText($text) : null;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        if ($lastError !== null) {
            Log::warning($label.' failed', ['message' => $lastError]);
        }

        return null;
    }

    private function plainText(string $text): string
    {
        $text = preg_replace('/<think>[\s\S]*?<\/think>/', '', $text) ?? $text;
        $text = preg_replace('/```[\s\S]*?```/', '', $text) ?? $text;
        $text = preg_replace('/^#{1,6}\s*/m', '', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '$1', $text) ?? $text;
        $text = preg_replace('/^\s*[\*\-]\s+/m', '• ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::connectTimeout(8)
            ->timeout((int) config('assistant.timeout', 25))
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->acceptJson();

        // Local XAMPP often lacks a CA bundle; skip verify in local only.
        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function systemPrompt(?User $user, ?string $page, string $guide = ''): string
    {
        $name = $user?->first_name ?: 'there';
        $role = $user?->role?->role_name ?: 'staff';
        $page = $page ?: 'unknown';

        $prompt = <<<PROMPT
You are the friendly in-app assistant for CSUCC Enrollment Analytics (Caraga State University Cabadbaran Campus — CEIT Program enrollment decision support).

Speak like a helpful coworker. Remember earlier messages. When someone asks what a feature is or how it works, give a full explanation: what it is, how this app calculates or uses it, and one worked example with sample results. Use short paragraphs and lines that start with "• ". Write plain text only. Do not use markdown, asterisks, or hash headings. Do not stop at a one-line summary. Do not invent features that are not listed below. Never claim you can change data yourself — guide the user to the right screen. This system supports decisions; it does not make administrative decisions automatically.

What-if analysis is a simulation, not a forecast. Recruitment percent applies only to new students. Retention percent applies only to returning students. Combined = current total + both changes. If combined is above planning capacity, the capped result is the capacity. Example: current 1,200, new 300, returning 900, recruitment +10% (+30 → 1,230), retention +5% (+45 → 1,245), combined 1,275. If capacity is 1,100, the capped outcome is 1,100 and the simulation exceeds capacity.

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

If the user greets you, greet back warmly and offer help. Follow up on their last topic when they say things like "yes", "how?", "then what?", or "show me". Answer questions about every part of this system, including pages the user is not currently on.
PROMPT;

        $guide = trim($guide);
        if ($guide !== '') {
            $prompt .= "\n\nVerified guide for this question. Explain it fully in your own words. Keep its steps, screen names, and sample results. Do not replace those numbers:\n".$guide;
        }

        return $prompt;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, links: list<array{label: string, url: string}>}
     */
    private function localConversation(string $message, ?User $user, ?string $page, array $history): array
    {
        if (preg_match('/^(hi|hello|hey|good\s+(morning|afternoon|evening))\b/i', $message)) {
            $name = $user?->first_name ?: 'there';

            return [
                'reply' => "Hi {$name}! I'm your CSUCC assistant. We can chat about anything in this system — students, enrollment, analytics, forecasting, AI insights, accounts, or settings. What are you trying to do right now?",
                'links' => $this->linksFor($user, ['dashboard', 'students.index', 'ai-support.index']),
            ];
        }

        $matched = $this->matchedTopic($message, $history);

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
        $best = null;
        $bestLen = 0;
        $bestPriority = -1;
        foreach ($this->topics() as $topic) {
            foreach ($topic['keywords'] as $keyword) {
                if (! str_contains($text, $keyword)) {
                    continue;
                }
                $length = strlen($keyword);
                $priority = (int) ($topic['priority'] ?? 0);
                if ($length > $bestLen || ($length === $bestLen && $priority > $bestPriority)) {
                    $best = $topic;
                    $bestLen = $length;
                    $bestPriority = $priority;
                }
            }
        }

        return $best;
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
     * @return array{keywords: list<string>, answer: string, routes?: list<string>}|null
     */
    private function matchedTopic(string $message, array $history): ?array
    {
        $matched = $this->matchTopic(Str::lower($message));
        $followUp = (bool) preg_match('/^(yes|yeah|yep|ok|okay|sure|how\??|then what|and then|continue|tell me more|show me|please|go on)\b/i', trim($message));
        if (! $matched && $followUp) {
            $matched = $this->lastAssistantTopic($history);
        }

        return $matched;
    }

    /**
     * @param  array{keywords?: list<string>, answer?: string, routes?: list<string>}|null  $topic
     * @return list<array{label: string, url: string}>
     */
    private function linksForReply(string $message, string $reply, ?User $user, ?array $topic): array
    {
        $fromTopic = $this->linksFor($user, $topic['routes'] ?? []);
        $fromReply = $this->suggestLinks($message.' '.$reply, $user);
        $seen = [];
        $links = [];
        foreach ([...$fromTopic, ...$fromReply] as $link) {
            $url = $link['url'] ?? '';
            if ($url === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $links[] = $link;
        }

        return array_slice($links, 0, 4);
    }

    private function whatIfGuide(): string
    {
        $example = app(ScenarioService::class)->run(1200, 300, 900, 10.0, 5.0, 1100);
        $current = number_format($example['current']['total']);
        $recruitment = number_format($example['recruitment']['total']);
        $retention = number_format($example['retention']['total']);
        $combined = number_format($example['combined']['total']);
        $capacity = number_format($example['combined']['capacity']);
        $capped = number_format($example['combined']['capped']);

        return <<<TEXT
What-if Analysis is a simulation. You type assumptions and the screen shows what the headcount would be if those assumptions were true. It is not a forecast. A forecast follows past enrollment. A what-if follows the numbers you enter, and it does not enroll or refuse any student.

The screen starts from the latest enrollment: current total, new students, and returning students. Three assumptions change the result:
• Recruitment percent applies only to new students.
• Retention percent applies only to returning students.
• Planning capacity is the ceiling. If the combined total is higher, the capped outcome stops at capacity.

Worked example, using the same formula as Run simulation:
• Current total {$current}. New students 300. Returning students 900.
• Recruitment +10% adds 30 new students, so that scenario is {$recruitment}.
• Retention +5% adds 45 returning students, so that scenario is {$retention}.
• Combined is current + both changes = {$combined}.
• Planning capacity {$capacity} is lower than {$combined}, so the capped outcome is {$capped} and the simulation exceeds capacity.

On the What-if screen, enter the percents and the capacity, then choose Run simulation. Read the current, recruitment, retention, and combined cards. If the combined figure exceeds capacity, the red note means the assumption does not fit the plan. People still decide what to do with that result.
TEXT;
    }

    /** @return list<array{keywords: list<string>, answer: string, routes?: list<string>}> */
    private function topics(): array
    {
        return [
            [
                'keywords' => ['student', 'add a student', 'create a student', 'edit a student', 'delete a student'],
                'answer' => "Student Records store one academic row per student per term. The row uses the Student ID. It does not store a personal name.\n\nHow to add one:\n1. Open Student Records.\n2. Choose Add Student if your account can manage enrollment.\n3. Enter the Student ID, program, year level, section, and the term.\n4. Save. The matching enrollment count for that year, semester, course, and year level goes up by one.\n\nExample: Student ID 2025-04011, BS Information Technology, 1st year, section A, is saved for the current term. Student Records shows that one row. Enrollment for BSIT 1st year in that term increases by 1. Actions on the row are View, Edit, and Delete.\n\nImport File accepts CSV or Excel (.xlsx): download the template, upload, preview, then confirm. Export downloads the rows that match the current filters.\n\nCEIT programs include Information Technology, Computer Engineering, and Electrical Engineering.",
                'routes' => ['students.index', 'students.create', 'students.import', 'students.export'],
            ],
            [
                'keywords' => ['enrollment', 'import', 'csv', 'template', 'data quality', 'upload'],
                'answer' => "Enrollment is the headcount summary. It is not a second copy of each student. Counts are grouped by academic year, semester, course, and year level.\n\nHow a count changes: saving or importing a student record adds that student to the matching group. Deleting the record removes them from the count.\n\nExample: the BSIT 1st year group already has 40 students. You import a file that preview shows 5 new BSIT 1st year rows and 0 errors. After you confirm, that group becomes 45. Data Quality lists gaps such as a missing semester or a duplicated group so you can fix the source records before you trust the count.\n\nImport path: download the CSV template, fill it, upload, read the preview, then confirm. Export downloads the counts you are currently viewing.",
                'routes' => ['enrollment.index', 'enrollment.quality', 'students.import'],
            ],
            [
                'keywords' => ['analytic', 'trend', 'retention', 'program analytic', 'college analytic', 'dashboard'],
                'answer' => "Analytics read the verified enrollment counts and turn them into planning views. They do not change the records.\n\n• Summary: the current totals and how they compare with the previous period.\n• Trends: the same count drawn across academic years.\n• Programs and colleges: which course or college holds the students.\n• Retention: how many students continue from one period to the next.\n• Dashboard and Executive view: the short KPI version of those totals.\n\nExample: BSIT is 120 this year and was 100 last year. Trends shows the rise. The program view shows BSIT as the larger share if Computer Engineering is 80 and Electrical Engineering is 60. Retention answers a different question: of last year's students, how many are still enrolled, not how many new students arrived.",
                'routes' => ['dashboard', 'executive', 'analytics.summary', 'analytics.trends', 'analytics.programs', 'analytics.colleges', 'analytics.retention'],
            ],
            [
                'keywords' => ['forecast', 'predict', 'projection'],
                'answer' => "Forecasting estimates a future headcount from enrollment history. It needs at least two past periods. It compares statistical models, keeps the one with the smallest error (MAE), and saves a predicted count with a lower and an upper bound.\n\nExample shape: a program has 800 students in one year and 920 in the next. Generate forecast. The screen picks the closer-fitting model and shows one predicted headcount for the following period, plus a lower bound and an upper bound around it. If only one period exists, it stops and asks for more history.\n\nUse the result for planning. It is not an automatic quota and it is not the same tool as What-if Analysis. A forecast follows past data. A what-if simulation follows assumptions you type.",
                'routes' => ['forecasting.index'],
            ],
            [
                'priority' => 20,
                'keywords' => ['what-if', 'what if', 'scenario', 'recruitment', 'retention improvement', 'planning capacity'],
                'answer' => $this->whatIfGuide(),
                'routes' => ['ai-support.what-if', 'ai-support.index'],
            ],
            [
                'keywords' => ['ai support', 'insight', 'decision support', 'accept or reject'],
                'answer' => "AI Decision Support reads analytics and writes insights a person can review. The system does not accept or reject them for you.\n\n1. Open AI Decision Support and generate insights.\n2. Open one insight and read the evidence.\n3. Mark it Accepted or Rejected. That status is your review, not an administrative decision.\n\nExample: an insight says BSIT grew faster than the other CEIT programs. You open it, compare it with the program analytics, then Accept it if the evidence matches or Reject it if the comparison is not useful. What-if Analysis is the separate screen where you type recruitment, retention, and capacity assumptions and see a simulated headcount.",
                'routes' => ['ai-support.index', 'ai-support.what-if'],
            ],
            [
                'keywords' => ['alert', 'notification', 'warning'],
                'answer' => "Alerts point at unusual enrollment or data problems so you can look at them. Opening an alert does not change the student record.\n\nExample: an alert says one program's count dropped sharply. Open the alert, follow it to the student or enrollment screen, and check whether a term is missing or a real drop happened. Mark it read after you have looked. Regenerate builds a fresh list from the current analytics.",
                'routes' => ['alerts.index'],
            ],
            [
                'keywords' => ['report', 'pdf', 'generate report'],
                'answer' => "Reports turn the current analytics into a summary you can share. Export on Students or Enrollment is different: that downloads the raw rows as CSV.\n\nExample: you generate a report after the term is verified. It restates the totals, trends, and notes already on the analytics screens. If a dean asks for the student-level file instead, use Export on Student Records with the filters set to that college and term.",
                'routes' => ['reports.index', 'students.export', 'enrollment.export'],
            ],
            [
                'keywords' => ['academic year', 'semester', 'college', 'department', 'program', 'course', 'data management', 'bsit', 'bscpe', 'bsee'],
                'answer' => "Data Management is the list of choices the rest of the system uses: academic years, semesters, colleges, departments, and programs.\n\nExample: the next school year is not in the filters yet. Add it under Academic Years, then add its semesters. After that, Add Student and the enrollment filters can use that year. A new CEIT course is added under Programs and linked to its department, so it can be selected on a student record.",
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
                'answer' => "Accounts and privileges decide which menus a person can open.\n\n• Staff can view SPR records, print the student QR, edit an SPR, and view at-risk students.\n• An Administrator can also record at-risk interventions and add or import enrollment data.\n• A student account only has the student portal.\n• A Super Administrator has every privilege, including assigning privileges and delete requests.\n\nExample: a staff account can open SPR and print the QR, but the At-Risk intervention form stays hidden until an administrator grants Record at-risk interventions. Approvals is where a new registration is accepted or blocked.",
                'routes' => ['users.index', 'approvals.index', 'delete-requests.index', 'login-logs.index'],
            ],
            [
                'keywords' => ['password', 'forgot', 'otp', 'secret question', 'login', 'sign in', 'credentials'],
                'answer' => "Sign-in uses the Employee ID or username, then the password. When the account requires it, a one-time code is sent before the session opens.\n\nForgot password, in order:\n1. Enter the Employee ID.\n2. Answer the security questions saved on the account.\n3. Enter the code sent to the account email.\n4. Choose a new password.\n\nExample: Employee ID EMP-0003 forgets the password, answers the three saved questions, types the email code, and sets a new password. The next sign-in uses that new password. Profile and theme are in the top-right menu after you are signed in.",
                'routes' => ['profile.edit', 'password.request', 'login'],
            ],
            [
                'keywords' => ['setting', 'theme', 'dark mode', 'appearance', 'profile', 'avatar'],
                'answer' => "Profile & Appearance changes only your own screen: theme color, background, and dark or light mode. System Settings, when your role includes it, changes campus-wide options such as mail.\n\nExample: you set the theme to green and dark mode. The sidebar and buttons follow that color on your next pages. Another staff account keeps its own theme. A mail setting under System Settings affects password codes for the campus, not just your profile.",
                'routes' => ['profile.edit', 'settings.index'],
            ],
            [
                'priority' => 15,
                'keywords' => ['at-risk', 'at risk', 'intervention'],
                'answer' => "At-Risk lists students whose grades, attendance, failed subjects, or enrollment standing need attention. Family and income details from the SPR can explain the situation. They do not raise the risk level by themselves.\n\nExample: a student has a GPA of 3.25, 70% attendance, and 2 failed subjects. The report can class that as critical from the academic figures. An SPR note that the household income is low appears as context. It does not move a student who is academically sound into a higher risk level. Staff can open the report. Recording an intervention requires the at-risk manage privilege.",
                'routes' => ['at-risk.index'],
            ],
            [
                'priority' => 12,
                'keywords' => ['spr', 'student personal record', 'signature', 'qr code', 'oasfa'],
                'answer' => "The Student Personal Record (SPR) is the blank form students fill without signing in. Staff share the link or print the QR code from the SPR page. The code opens that same link.\n\nBefore saving, the student sees three warnings: check every answer because the form locks after save, visit the Office of OASFA for further concerns, and keep the signature inside the box so the frame does not cut it off.\n\nExample: two forms with the same last name, first name, middle name, and birth date cannot both be saved. The second attempt stays unsaved and repeats the OASFA warning. A different name, or the same person editing the saved record, can still be saved. After a successful save, the student cannot edit it unless staff allow one edit.",
                'routes' => ['spes.index'],
            ],
            [
                'keywords' => ['risk', 'gpa', 'attendance', 'scholarship', 'tuition', 'filter'],
                'answer' => "On Student Records, the filter bar narrows the list by year, semester, college, course, year level, section, gender, enrollment, scholarship, tuition, and risk.\n\nExample: filter to BSIT, 1st year, and high risk. The table shows only those rows. The risk badge summarizes GPA, grades, attendance, and failed subjects for that term. Export then downloads just that filtered list, not the whole campus.",
                'routes' => ['students.index'],
            ],
            [
                'keywords' => ['export', 'download'],
                'answer' => "Export downloads what the screen is showing after filters. On Student Records that is student rows. On Enrollment that is the grouped counts.\n\nExample: filter Student Records to one college and one term, then Export. The CSV contains those rows only. Import is the opposite direction: Student Records → Import File → template, upload, preview, confirm.",
                'routes' => ['students.export', 'students.import', 'students.template', 'enrollment.export'],
            ],
            [
                'keywords' => ['permission', 'access denied', 'cannot see', "can't see", 'unauthorized', 'forbidden'],
                'answer' => "A missing menu means the account does not have that privilege. The screen is hidden rather than shown as an error.\n\nExample: a staff account opens SPR and the printable QR, but cannot record an at-risk intervention. An administrator opens Privileges for that account and checks Record at-risk interventions. After saving, the intervention form appears for that account. Super Administrator already has every privilege.",
                'routes' => ['profile.edit', 'users.index'],
            ],
            [
                'keywords' => ['ceit', 'cabadbaran', 'csucc', 'about', 'scope'],
                'answer' => "This system supports CEIT enrollment planning at Caraga State University Cabadbaran Campus. It covers Information Technology, Computer Engineering, and Electrical Engineering.\n\nIt shows records, counts, analytics, forecasts, what-if simulations, and reviewable insights. A person still accepts an insight, changes a record, or sets a quota. The system does not make that administrative decision.\n\nExample: a what-if result of 1,275 against a capacity of 1,100 tells you the assumption exceeds the plan. It does not enroll or refuse any student.",
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
            str_contains($page, '/ai-support/what-if') => 'You are on the What-if Analysis screen. Run simulation uses the same formula as the example.',
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
            $out[] = ['role' => $role, 'content' => Str::limit($content, 1800, '…')];
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
            'spes.index' => 'SPR',
            'at-risk.index' => 'At-Risk',
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
            'spes.index' => 'spr.view',
            'at-risk.index' => 'at_risk.view',
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
