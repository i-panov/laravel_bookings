<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Booking::truncate();
        BookingSlot::truncate();

        for ($i = 0; $i < 3; $i++) {
            $user = new User();
            $user->id = $i + 1;
            $user->name = "User {$user->id}";
            $user->api_token = "token{$user->id}";
            $user->save();
        }

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
