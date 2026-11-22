<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->unique()->after('photo_url');
            }
        });

        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::create('bus_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('destination_city_id')->constrained('cities')->restrictOnDelete();
            $table->unsignedInteger('distance_km')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['agency_id', 'status']);
        });

        Schema::create('bus_route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_route_id')->constrained('bus_routes')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedSmallInteger('stop_order');
            $table->unsignedInteger('offset_minutes')->default(0);
            $table->timestamps();
            $table->unique(['bus_route_id', 'stop_order']);
        });

        Schema::create('bus_route_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_route_id')->constrained('bus_routes')->cascadeOnDelete();
            $table->foreignId('from_stop_id')->constrained('bus_route_stops')->cascadeOnDelete();
            $table->foreignId('to_stop_id')->constrained('bus_route_stops')->cascadeOnDelete();
            $table->foreignId('bus_class_id')->nullable()->constrained('bus_classes')->nullOnDelete();
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('XAF');
            $table->timestamps();
            $table->index(['bus_route_id', 'from_stop_id', 'to_stop_id']);
        });

        Schema::create('bus_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_route_id')->constrained('bus_routes')->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained('buses')->restrictOnDelete();
            $table->foreignId('default_crew_id')->nullable()->constrained('crew')->nullOnDelete();
            $table->time('departure_time');
            $table->json('days_of_week');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['bus_route_id', 'is_active']);
        });

        Schema::create('bus_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_route_id')->constrained('bus_routes')->restrictOnDelete();
            $table->foreignId('agency_id')->constrained()->restrictOnDelete();
            $table->foreignId('bus_id')->constrained('buses')->restrictOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('bus_schedules')->nullOnDelete();
            $table->foreignId('crew_id')->nullable()->constrained('crew')->nullOnDelete();
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->unsignedSmallInteger('sales_cutoff_minutes')->default(30);
            $table->json('allowed_channels')->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();
            $table->index(['status', 'departure_at']);
            $table->index(['agency_id', 'status', 'departure_at']);
        });

        Schema::create('bus_trip_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_trip_id')->constrained('bus_trips')->cascadeOnDelete();
            $table->string('seat_label', 20);
            $table->string('seat_type')->default('standard');
            $table->string('status')->default('available');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('XAF');
            $table->dateTime('hold_expires_at')->nullable();
            $table->foreignId('booking_id')->nullable();
            $table->foreignId('ticket_id')->nullable();
            $table->timestamps();
            $table->unique(['bus_trip_id', 'seat_label']);
            $table->index(['bus_trip_id', 'status']);
        });

        Schema::create('bus_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_ref', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel')->default('web');
            $table->foreignId('agency_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sold_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_phone', 40);
            $table->string('contact_email')->nullable();
            $table->string('status')->default('held');
            $table->dateTime('hold_expires_at')->nullable();
            $table->unsignedBigInteger('total_minor');
            $table->char('currency', 3)->default('XAF');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
            $table->index(['agency_id', 'status', 'created_at']);
        });

        Schema::table('bus_trip_seats', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bus_bookings')->nullOnDelete();
        });

        Schema::create('bus_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 48)->unique();
            $table->foreignId('booking_id')->constrained('bus_bookings')->restrictOnDelete();
            $table->foreignId('bus_trip_id')->constrained('bus_trips')->restrictOnDelete();
            $table->foreignId('bus_trip_seat_id')->constrained('bus_trip_seats')->restrictOnDelete();
            $table->foreignId('from_stop_id')->nullable()->constrained('bus_route_stops')->nullOnDelete();
            $table->foreignId('to_stop_id')->nullable()->constrained('bus_route_stops')->nullOnDelete();
            $table->string('passenger_name');
            $table->string('passenger_phone', 40)->nullable();
            $table->unsignedBigInteger('fare_minor');
            $table->char('currency', 3)->default('XAF');
            $table->string('status')->default('issued');
            $table->string('channel')->default('web');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sold_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('sold_at')->nullable();
            $table->dateTime('scanned_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_token_hash', 64)->unique();
            $table->timestamps();
            $table->index(['bus_trip_id', 'status']);
            $table->index(['branch_id', 'sold_at']);
        });

        Schema::table('bus_trip_seats', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('bus_tickets')->nullOnDelete();
        });

        Schema::create('bus_ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_ticket_id')->constrained('bus_tickets')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->string('source')->default('system');
            $table->timestamps();
            $table->index(['bus_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_ticket_events');
        Schema::table('bus_trip_seats', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });
        Schema::dropIfExists('bus_tickets');
        Schema::table('bus_trip_seats', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });
        Schema::dropIfExists('bus_bookings');
        Schema::dropIfExists('bus_trip_seats');
        Schema::dropIfExists('bus_trips');
        Schema::dropIfExists('bus_schedules');
        Schema::dropIfExists('bus_route_fares');
        Schema::dropIfExists('bus_route_stops');
        Schema::dropIfExists('bus_routes');
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['firebase_uid']);
            $table->dropColumn('firebase_uid');
        });
    }
};
