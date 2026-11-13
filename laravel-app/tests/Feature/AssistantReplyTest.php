<?php

namespace Tests\Feature;

use App\Services\AssistantService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantReplyTest extends TestCase
{
    public function test_what_if_answer_includes_a_full_worked_example(): void
    {
        $reply = app(AssistantService::class)->reply('Explain what-if analysis with an example', null, '/ai-support/what-if');

        $this->assertSame('local', $reply['mode']);
        $this->assertStringContainsString('not a forecast', $reply['reply']);
        $this->assertStringContainsString('1,230', $reply['reply']);
        $this->assertStringContainsString('1,245', $reply['reply']);
        $this->assertStringContainsString('1,275', $reply['reply']);
        $this->assertStringContainsString('1,100', $reply['reply']);
        $this->assertStringContainsString('Run simulation', $reply['reply']);
    }

    public function test_other_topics_also_include_an_example_result(): void
    {
        $assistant = app(AssistantService::class);

        $student = $assistant->reply('How do I add a student?');
        $this->assertStringContainsString('2025-04011', $student['reply']);
        $this->assertStringContainsString('increases by 1', $student['reply']);

        $forecast = $assistant->reply('How does forecasting work?');
        $this->assertStringContainsString('800', $forecast['reply']);
        $this->assertStringContainsString('lower bound', $forecast['reply']);
    }

    public function test_configured_model_answers_system_questions_instead_of_the_script(): void
    {
        config([
            'assistant.provider' => 'openai',
            'assistant.openai.api_key' => 'test-key',
            'assistant.openai.base_url' => 'https://example.test/v1',
            'assistant.openai.model' => 'test-model',
        ]);

        Http::fake([
            'example.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => "### What-if\n**What-if** keeps the worked example at 1,230 and is not a forecast."],
                ]],
            ]),
        ]);

        $reply = app(AssistantService::class)->reply('Explain what-if analysis with an example', null, '/dashboard');

        $this->assertSame('openai', $reply['mode']);
        $this->assertStringContainsString('1,230', $reply['reply']);
        $this->assertStringNotContainsString('**', $reply['reply']);
        $this->assertStringNotContainsString('###', $reply['reply']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'example.test')
            && str_contains((string) $request['messages'][0]['content'], '1,230'));
    }
}
