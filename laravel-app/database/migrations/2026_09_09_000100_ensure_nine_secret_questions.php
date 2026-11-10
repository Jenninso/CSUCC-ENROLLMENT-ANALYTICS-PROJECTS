<?php

use App\Models\SecretQuestion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('secret_questions') as $question) {
            SecretQuestion::query()->firstOrCreate(
                ['question' => $question],
                ['is_active' => true]
            );
        }
    }

    public function down(): void
    {
        // Keep existing questions; they may already be linked to user answers.
    }
};
