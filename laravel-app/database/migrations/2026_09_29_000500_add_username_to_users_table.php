<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('email');
        });

        $used = [];
        $users = DB::table('users')->select('id', 'email')->orderBy('id')->get();
        foreach ($users as $user) {
            $base = strtolower((string) strstr((string) $user->email, '@', true));
            $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'user'.$user->id;
            $base = substr($base, 0, 40);
            $candidate = $base;
            $suffix = 1;
            while (isset($used[$candidate])) {
                $candidate = substr($base, 0, 40 - strlen((string) $suffix)).$suffix;
                $suffix++;
            }
            $used[$candidate] = true;
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
