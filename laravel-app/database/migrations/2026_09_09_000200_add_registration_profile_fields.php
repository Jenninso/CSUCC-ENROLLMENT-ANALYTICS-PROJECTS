<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('extension_name', 20)->nullable()->after('last_name');
            $table->date('birthdate')->nullable()->after('extension_name');
            $table->string('sex', 20)->nullable()->after('birthdate');
            $table->string('purok_street')->nullable()->after('address');
            $table->string('barangay')->nullable()->after('purok_street');
            $table->string('city_municipality')->nullable()->after('barangay');
            $table->string('province')->nullable()->after('city_municipality');
            $table->string('zipcode', 10)->nullable()->after('province');
            $table->string('contact_number', 20)->nullable()->after('zipcode');
        });

        Schema::table('registration_requests', function (Blueprint $table) {
            $table->string('extension_name', 20)->nullable()->after('last_name');
            $table->date('birthdate')->nullable()->after('extension_name');
            $table->string('sex', 20)->nullable()->after('birthdate');
            $table->string('purok_street')->nullable()->after('email');
            $table->string('barangay')->nullable()->after('purok_street');
            $table->string('city_municipality')->nullable()->after('barangay');
            $table->string('province')->nullable()->after('city_municipality');
            $table->string('zipcode', 10)->nullable()->after('province');
            $table->string('contact_number', 20)->nullable()->after('zipcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'extension_name',
                'birthdate',
                'sex',
                'purok_street',
                'barangay',
                'city_municipality',
                'province',
                'zipcode',
                'contact_number',
            ]);
        });

        Schema::table('registration_requests', function (Blueprint $table) {
            $table->dropColumn([
                'extension_name',
                'birthdate',
                'sex',
                'purok_street',
                'barangay',
                'city_municipality',
                'province',
                'zipcode',
                'contact_number',
            ]);
        });
    }
};
