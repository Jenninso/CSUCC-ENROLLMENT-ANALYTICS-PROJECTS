<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_insights', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('situation')->nullable()->after('description');
            $table->text('evidence')->nullable()->after('situation');
            $table->text('interpretation')->nullable()->after('evidence');
            $table->text('forecast_note')->nullable()->after('interpretation');
            $table->unsignedTinyInteger('confidence')->nullable()->after('recommendation');
            $table->string('status')->default('pending_review')->after('confidence');
        });

        Schema::table('enrollment_records', function (Blueprint $table) {
            $table->index(['academic_year_id', 'semester_id']);
            $table->index(['program_id', 'academic_year_id']);
            $table->index('enrollment_type');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_records', function (Blueprint $table) {
            $table->dropIndex(['academic_year_id', 'semester_id']);
            $table->dropIndex(['program_id', 'academic_year_id']);
            $table->dropIndex(['enrollment_type']);
        });

        Schema::table('ai_insights', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn([
                'situation',
                'evidence',
                'interpretation',
                'forecast_note',
                'confidence',
                'status',
            ]);
        });
    }
};
