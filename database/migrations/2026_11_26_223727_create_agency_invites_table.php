<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->nullable()->constrained('bus_stations')->nullOnDelete();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('role')->default('sub_agent');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_invites');
    }
};