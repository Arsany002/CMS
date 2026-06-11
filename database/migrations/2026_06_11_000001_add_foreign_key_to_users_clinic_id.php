<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // clinic_id was originally an unsignedBigInteger with no FK constraint.
            // Clinics use UUIDs, so we must change the column type before adding the constraint.
            $table->foreignUuid('clinic_id')->nullable()->change();
            $table->foreign('clinic_id')->references('id')->on('clinics')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->unsignedBigInteger('clinic_id')->nullable()->change();
        });
    }
};
