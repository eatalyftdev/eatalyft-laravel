<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cities', function (Blueprint $t) {
            $t->id(); $t->string('name_en'); $t->string('name_fr'); $t->string('region')->nullable(); $t->string('country',2)->default('CM'); $t->timestamps();
        });
        Schema::create('bus_classes', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('code')->unique(); $t->timestamps(); });
        Schema::create('amenities', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('icon')->nullable(); $t->timestamps(); });
        Schema::create('branches', function (Blueprint $t) {
            $t->id(); $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->foreignId('city_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->enum('type',['office','terminal'])->default('office');
            $t->string('phone')->nullable(); $t->enum('status',['active','inactive'])->default('active');
            $t->timestamps();
        });
        Schema::create('buses', function (Blueprint $t) {
            $t->id(); $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->string('plate')->unique(); $t->string('fleet_no')->nullable();
            $t->string('make')->nullable(); $t->string('model')->nullable(); $t->unsignedSmallInteger('year')->nullable();
            $t->foreignId('bus_class_id')->nullable()->constrained('bus_classes')->nullOnDelete();
            $t->foreignId('seat_layout_id')->nullable();
            $t->unsignedSmallInteger('capacity')->default(0);
            $t->json('amenities')->nullable(); $t->json('photos')->nullable();
            $t->enum('status',['active','maintenance','retired'])->default('active');
            $t->timestamps();
        });
        Schema::create('bus_documents', function (Blueprint $t) {
            $t->id(); $t->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $t->string('type'); $t->string('file_path'); $t->date('expiry_date')->nullable(); $t->timestamps();
        });
        Schema::create('maintenance_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $t->date('date'); $t->string('type'); $t->unsignedInteger('odometer')->nullable();
            $t->text('notes')->nullable(); $t->date('next_due_date')->nullable(); $t->timestamps();
        });
        Schema::create('crew', function (Blueprint $t) {
            $t->id(); $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->string('phone'); $t->string('role')->default('driver');
            $t->string('licence_number')->nullable(); $t->date('licence_expiry')->nullable();
            $t->timestamps();
        });
        Schema::create('seat_layouts', function (Blueprint $t) {
            $t->id(); $t->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name'); $t->unsignedSmallInteger('rows')->default(0);
            $t->unsignedSmallInteger('cols')->default(0); $t->timestamps();
        });
        Schema::create('seat_layout_cells', function (Blueprint $t) {
            $t->id(); $t->foreignId('seat_layout_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('row'); $t->unsignedSmallInteger('col');
            $t->enum('type',['seat','aisle','door','driver','blocked'])->default('seat');
            $t->string('label')->nullable(); $t->string('class')->nullable(); $t->timestamps();
            $t->unique(['seat_layout_id','row','col']);
        });
    }
    public function down(): void {
        foreach (['seat_layout_cells','seat_layouts','crew','maintenance_logs','bus_documents','buses','branches','amenities','bus_classes','cities'] as $t) Schema::dropIfExists($t);
    }
};
