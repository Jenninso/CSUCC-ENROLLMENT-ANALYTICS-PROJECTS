<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained();
            $table->foreignId('college_id')->nullable()->constrained();
            $table->string('forecast_period');
            $table->integer('predicted_enrollment');
            $table->integer('lower_bound')->nullable();
            $table->integer('upper_bound')->nullable();
            $table->string('model_used');
            $table->decimal('accuracy_metric', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
