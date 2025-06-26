<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMultipleSlotsRequest;
use App\Http\Requests\StoreSingleSlotRequest;
use App\Models\Booking;
use App\Models\BookingSlot;

class BookingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $bookings = $user->bookings()
            ->with('slots')
            ->get();

        return response()->json(['bookings' => $bookings]);
    }

    public function create(StoreMultipleSlotsRequest $request)
    {
        \DB::beginTransaction();

        try {
            $user = auth()->user();
            $booking = $user->bookings()->create();

            $slotsData = collect($request->slots)->map(function ($slotData) use ($booking) {
                return [
                    'booking_id' => $booking->id,
                    'start_time' => $slotData['start_time'],
                    'end_time' => $slotData['end_time'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            BookingSlot::insert($slotsData);

            $bookingWithSlots = $booking->load('slots');

            \DB::commit();

            return response()->json([
                'booking' => $bookingWithSlots
            ], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new AppException('Не удалось создать бронирование', 500, $e);
        }
    }

    public function updateSlot(Booking $booking, BookingSlot $slot, StoreSingleSlotRequest $request)
    {
        try {
            $slot->start_time = $request->validated('start_time');
            $slot->end_time = $request->validated('end_time');
            $slot->save();
            return response()->json(['slot' => $slot]);
        } catch (\Exception $e) {
            throw new AppException('Не удалось обновить слот', 500, $e);
        }
    }

    public function addSlot(Booking $booking, StoreSingleSlotRequest $request)
    {
        try {
            $slot = $booking->slots()->create($request->validated());
            return response()->json(['slot' => $slot], 201);
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
