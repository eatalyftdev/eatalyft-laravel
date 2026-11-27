<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cities')) {
            // Table already exists from earlier migration; alter columns
            Schema::table('cities', function (Blueprint $table) {
                if (!Schema::hasColumn('cities', 'code')) {
                    $table->string('code', 3)->unique()->after('name');
                }
                if (!Schema::hasColumn('cities', 'slug')) {
                    $table->string('slug')->unique()->after('code');
                }
                if (!Schema::hasColumn('cities', 'country_code')) {
                    $table->string('country_code', 3)->default('CM')->after('id');
                }
                if (!Schema::hasColumn('cities', 'is_enabled')) {
                    $table->boolean('is_enabled')->default(true)->after('code');
                }
            });
            return;
        }

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 3)->default('CM');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 3)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};