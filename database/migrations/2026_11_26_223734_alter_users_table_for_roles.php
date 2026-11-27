<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('customer')->after('email');
            }
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status')->default('active')->after('role');
            }
            if (!Schema::hasColumn('users', 'phone_e164')) {
                $table->string('phone_e164')->nullable()->index()->after('account_status');
            }
            if (!Schema::hasColumn('users', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete()->after('phone_e164');
            }
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status')->default('unverified')->after('city_id');
            }
            if (!Schema::hasColumn('users', 'kyc_tier')) {
                $table->string('kyc_tier')->default('basic')->after('kyc_status');
            }
            if (!Schema::hasColumn('users', 'otp_verified_at')) {
                $table->timestamp('otp_verified_at')->nullable()->after('kyc_tier');
            }
            if (!Schema::hasColumn('users', 'agency_id')) {
                $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete()->after('otp_verified_at');
            }
            if (!Schema::hasColumn('users', 'station_id')) {
                $table->foreignId('station_id')->nullable()->constrained('bus_stations')->nullOnDelete()->after('agency_id');
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('station_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'account_status', 'phone_e164', 'city_id', 'kyc_status', 'kyc_tier', 'otp_verified_at', 'agency_id', 'station_id', 'must_change_password']);
        });
    }
};