<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\DTO\SlotData;

interface BookingServiceInterface {
    public function createBookingWithSlots(User $user, array $slots): Booking;
    public function updateBookingSlot(BookingSlot $slot, SlotData $data): BookingSlot;
    public function addSlotToBooking(Booking $booking, array $slotData): BookingSlot;
    public function deleteBooking(Booking $booking): void;
    public function getUserBookings(User $user);
}
