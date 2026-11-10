<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('otp_verifications')) {
            Schema::create('otp_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('email')->nullable()->index();
                $table->string('purpose', 64);
                $table->string('otp_hash');
                $table->timestamp('expires_at');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('max_attempts')->default(5);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('invalidated_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['email', 'purpose']);
                $table->index(['user_id', 'purpose']);
                $table->index(['purpose', 'expires_at']);
            });
        }

        if (Schema::hasTable('otps') && Schema::hasTable('otp_verifications')) {
            $rows = DB::table('otps')->orderBy('id')->get();
            foreach ($rows as $row) {
                DB::table('otp_verifications')->insert([
                    'user_id' => null,
                    'email' => $row->email,
                    'purpose' => $row->purpose,
                    'otp_hash' => $row->code_hash,
                    'expires_at' => $row->expires_at,
                    'attempts' => $row->attempts ?? 0,
                    'max_attempts' => 5,
                    'last_sent_at' => $row->created_at,
                    'verified_at' => $row->consumed_at,
                    'invalidated_at' => null,
                    'ip_address' => null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
