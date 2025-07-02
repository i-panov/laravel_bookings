<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\User;
use App\DTO\SlotData;
use App\Contracts\BookingServiceInterface;

class BookingService implements BookingServiceInterface
{
    public function createBookingWithSlots(User $user, array $slots): Booking
    {
        return \DB::transaction(function () use ($user, $slots) {
            $booking = $user->bookings()->create();
            $now = now();
            $slotsData = [];

            foreach ($slots as $slot) {
                $slotsData[] = [
                    'booking_id' => $booking->id,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            BookingSlot::insert($slotsData);
            return $booking->load('slots');
        });
    }

    public function updateBookingSlot(BookingSlot $slot, SlotData $data): BookingSlot
    {
        $slot->start_time = $data->start_time;
        $slot->end_time = $data->end_time;
        $slot->save();
        return $slot;
    }

    public function addSlotToBooking(Booking $booking, array $slotData): BookingSlot
    {
        return $booking->slots()->create($slotData);
    }

    public function deleteBooking(Booking $booking): void
    {
        $booking->delete();
    }

    public function getUserBookings(User $user)
    {
        return $user->bookings()->with('slots')->get();
    }
}
