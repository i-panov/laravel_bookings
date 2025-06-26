<?php

use App\Models\Booking;
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
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Booking::class)->constrained()->onDelete('CASCADE');
            $table->dateTimeTz('start_time');
            $table->dateTimeTz('end_time');
            $table->timestamps();

            $table->index(['booking_id', 'start_time', 'end_time']);
        });

        if (config('database.default') !== 'sqlite') {
            DB::statement('alter table booking_slots add constraint check_dates check (start_time < end_time)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
