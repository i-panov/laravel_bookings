<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMultipleSlotsRequest;
use App\Http\Requests\StoreSingleSlotRequest;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Contracts\BookingServiceInterface;
use App\DTO\SlotData;
use App\Http\Resources\BookingCollectionResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\SlotResource;

class BookingController extends Controller
{
    public function __construct(
        protected BookingServiceInterface $bookingService
    ) {}

    public function index()
    {
        $bookings = $this->bookingService->getUserBookings(auth()->user());
        return new BookingCollectionResource($bookings);
    }

    public function create(StoreMultipleSlotsRequest $request)
    {
        try {
            $booking = $this->bookingService->createBookingWithSlots(auth()->user(), $request->slots);
            return (new BookingResource($booking))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            throw new AppException('Не удалось создать бронирование', 500, $e);
        }
    }

    public function updateSlot(Booking $booking, BookingSlot $slot, StoreSingleSlotRequest $request)
    {
        try {
            $this->bookingService->updateBookingSlot($slot, new SlotData($request->start_time, $request->end_time));
            return new SlotResource($slot);
        } catch (\Exception $e) {
            throw new AppException('Не удалось обновить слот', 500, $e);
        }
    }

    public function addSlot(Booking $booking, StoreSingleSlotRequest $request)
    {
        try {
            $slot = $this->bookingService->addSlotToBooking($booking, $request->validated());
            return (new SlotResource($slot))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            throw new AppException('Не удалось добавить слот', 500, $e);
        }
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['success' => true]);
    }
}
