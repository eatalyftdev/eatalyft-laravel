<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('bus_trips')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bus_bookings')->cascadeOnDelete();
            $table->string('milestone'); // '60min', '15min', 'boarding', 'departed'
            $table->string('channel'); // 'whatsapp', 'database', 'broadcast'
            $table->string('recipient_phone')->nullable();
            $table->timestamp('dispatched_at');
            $table->timestamps();
            $table->unique(['trip_id', 'booking_id', 'milestone', 'channel'], 'tnd_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_notification_dispatches');
    }
};