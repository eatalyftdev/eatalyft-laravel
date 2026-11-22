<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('error_incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('fingerprint')->index();
            $table->string('severity')->index();
            $table->string('exception_class')->index();
            $table->text('message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->string('environment')->index();
            $table->string('request_method')->nullable();
            $table->string('request_url')->nullable();
            $table->string('route_name')->nullable();
            $table->integer('http_status')->nullable();
            $table->string('request_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->string('status')->default('open')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['fingerprint', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_incidents');
    }
};
