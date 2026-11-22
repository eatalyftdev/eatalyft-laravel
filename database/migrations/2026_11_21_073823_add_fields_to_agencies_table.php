<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('slug');
            $table->text('description')->nullable()->after('logo_url');
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete()->after('description');
            $table->string('website')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('status');
            $table->boolean('kyc_verified')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn(['logo_url', 'description', 'city_id', 'website', 'is_active', 'kyc_verified']);
        });
    }
};