<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('email');
            $table->string('theme_mode', 16)->default('light')->after('avatar_path');
            $table->string('theme_color', 32)->default('#0b5d3b')->after('theme_mode');
            $table->string('theme_background', 32)->default('default')->after('theme_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'theme_mode', 'theme_color', 'theme_background']);
        });
    }
};
