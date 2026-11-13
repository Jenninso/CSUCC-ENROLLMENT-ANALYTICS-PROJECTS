<?php

use App\Services\PrivilegeSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PrivilegeSync::class)->sync();
    }

    public function down(): void
    {
        // Role and account privileges stay in place. Removing them would lock staff out of SPR and at-risk.
    }
};
