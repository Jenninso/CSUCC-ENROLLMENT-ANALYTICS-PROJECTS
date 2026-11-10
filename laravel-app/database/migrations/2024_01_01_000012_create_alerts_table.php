<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('program_id')->nullable()->constrained();
            $table->string('metric');
            $table->decimal('current_value');
            $table->decimal('expected_value')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('explanation')->nullable();
            $table->text('recommended_action')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
