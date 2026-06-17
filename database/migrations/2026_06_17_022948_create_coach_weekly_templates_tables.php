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
        Schema::create('coach_weekly_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('template_name');
            $table->unsignedInteger('booking_open_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('coach_id');
            $table->index('is_active');
        });

        Schema::create('template_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('coach_weekly_templates')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday, 1 = Monday, etc.
            $table->string('session_name'); // e.g. Pagi / Sore
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location');
            $table->unsignedInteger('max_capacity');
            $table->unsignedInteger('duration_minutes');
            $table->timestamps();

            $table->index('template_id');
            $table->index('day_of_week');
        });

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_slot_id')->nullable()->constrained('template_slots')->nullOnDelete();
            $table->date('slot_date');
            $table->string('session_name');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location');
            $table->unsignedInteger('max_capacity');
            $table->unsignedInteger('current_bookings')->default(0);
            $table->string('status')->default('available'); // available, full, cancelled
            $table->timestamps();

            $table->index('coach_id');
            $table->index('slot_date');
            $table->index('status');
            $table->index('template_slot_id');
            $table->unique(['coach_id', 'slot_date', 'start_time', 'end_time'], 'coach_slot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
        Schema::dropIfExists('template_slots');
        Schema::dropIfExists('coach_weekly_templates');
    }
};
