<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_avatar')->default(false)->after('avatar_path');
        });

        Schema::create('user_avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mime', 64);
            $table->longText('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_avatars');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_avatar');
        });
    }
};
