<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('agencies', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique();
            $t->string('email')->nullable(); $t->string('phone')->nullable();
            $t->enum('status',['pending_approval','approved','rejected','suspended','changes_requested'])->default('pending_approval');
            $t->text('rejection_reason')->nullable(); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('agencies'); }
};
