<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('email_reminded_at')->nullable()->after('notes');
            $table->timestamp('app_reminded_at')->nullable()->after('email_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['email_reminded_at', 'app_reminded_at']);
        });
    }
};
