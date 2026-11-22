<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('email');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('last_name');
            $table->string('photo_url')->nullable()->after('phone');
            $table->string('firebase_uid')->nullable()->unique()->after('photo_url');
            $table->string('public_id')->nullable()->unique()->after('firebase_uid');
            $table->enum('status', ['active', 'pending', 'suspended', 'incomplete'])->default('pending')->after('public_id');
            $table->boolean('created_by_admin')->default(false)->after('status');
            $table->unsignedTinyInteger('kyc_tier')->default(0)->after('created_by_admin');
            $table->string('kyc_status')->default('NOT_STARTED')->after('kyc_tier');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            $table->string('currency_preference')->default('XAF')->after('kyc_verified_at');
            $table->timestamp('must_change_password_at')->nullable()->after('currency_preference');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'first_name', 'last_name', 'phone', 'photo_url',
                'firebase_uid', 'public_id', 'status', 'created_by_admin',
                'kyc_tier', 'kyc_status', 'kyc_verified_at', 'currency_preference',
                'must_change_password_at',
            ]);
        });
    }
};
