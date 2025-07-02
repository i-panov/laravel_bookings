<?php

use App\Models\BookingSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'api_token' => 'test_token_123'
    ]);
    
    $this->withHeaders([
        'Authorization' => 'Bearer test_token_123',
        'Accept' => 'application/json'
    ]);
});

test('пользователь может получить список своих бронирований', function () {
    $booking = $this->user->bookings()->create();
    $slot = $booking->slots()->create([
        'start_time' => now()->addHour(),
        'end_time' => now()->addHours(2),
    ]);

    $response = $this->get('/api/bookings');

    $response->assertOk()
        ->assertJson(function (AssertableJson $json) use ($slot, $booking) {
            $json->has('bookings', 1)
                ->has('bookings.0', function (AssertableJson $json) use ($slot, $booking) {
                    $json->where('id', $booking->id)
                        ->where('user_id', $this->user->id)
                        ->has('slots', 1)
                        ->has('slots.0', function (AssertableJson $json) use ($slot) {
                            $json->where('id', $slot->id)
                                ->etc();
                        })
                        ->etc();
                });
        });
});

test('пользователь может создать бронирование со слотами', function () {
    $slots = [
        [
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
        ],
        [
            'start_time' => now()->addHours(3)->toDateTimeString(),
            'end_time' => now()->addHours(4)->toDateTimeString(),
        ]
    ];

    $response = $this->postJson('/api/bookings', [
        'slots' => $slots
    ]);

    $response->assertCreated()
        ->assertJson(function (AssertableJson $json) {
            $json->has('booking')
                ->has('booking.slots', 2)
                ->where('booking.user_id', $this->user->id)
                ->etc();
        });
    
    $this->assertDatabaseCount('bookings', 1);
    $this->assertDatabaseCount('booking_slots', 2);
});

test('создание бронирования не проходит при невалидных слотах', function () {
    $slots = [
        [
            'start_time' => now()->subHour()->toDateTimeString(),
            'end_time' => now()->addHour()->toDateTimeString(),
        ]
    ];

    $response = $this->postJson('/api/bookings', [
        'slots' => $slots
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure([
            'error',
            'messages' => [
                'slots.0.start_time' => []
            ]
        ]);
});

test('пользователь может обновить слот', function () {
    $booking = $this->user->bookings()->create();
    $slot = $booking->slots()->create([
        'start_time' => now()->addHour(),
        'end_time' => now()->addHours(2),
    ]);

    $newData = [
        'start_time' => now()->addHours(3)->toDateTimeString(),
        'end_time' => now()->addHours(5)->toDateTimeString(),
    ];

    $response = $this->patchJson("/api/bookings/{$booking->id}/slots/{$slot->id}", $newData);

    $response->assertOk();
    
    $updatedSlot = BookingSlot::find($slot->id);
    $this->assertEquals($newData['start_time'], $updatedSlot->start_time);
    $this->assertEquals($newData['end_time'], $updatedSlot->end_time);
});

test('пользователь не может обновить слот в чужом бронировании', function () {
    $otherUser = User::factory()->create(['api_token' => 'other_token']);
    $booking = $otherUser->bookings()->create();
    $slot = $booking->slots()->create([
        'start_time' => now()->addHour(),
        'end_time' => now()->addHours(2),
    ]);

    $newData = [
        'start_time' => now()->addHours(3)->toDateTimeString(),
        'end_time' => now()->addHours(5)->toDateTimeString(),
    ];

    $response = $this->patchJson("/api/bookings/{$booking->id}/slots/{$slot->id}", $newData);

    $response->assertForbidden();
});

test('пользователь может добавить слот к своему бронированию', function () {
    $booking = $this->user->bookings()->create();

    $slotData = [
        'start_time' => now()->addHours(3)->toDateTimeString(),
        'end_time' => now()->addHours(5)->toDateTimeString(),
    ];

    $response = $this->postJson("/api/bookings/{$booking->id}/slots", $slotData);

    $response->assertCreated();
    
    $slot = BookingSlot::first();
    $this->assertEquals($slotData['start_time'], $slot->start_time);
    $this->assertEquals($slotData['end_time'], $slot->end_time);
});

test('пользователь не может добавить слот к чужому бронированию', function () {
    $otherUser = User::factory()->create(['api_token' => 'other_token']);
    $booking = $otherUser->bookings()->create();

    $slotData = [
        'start_time' => now()->addHours(3)->toDateTimeString(),
        'end_time' => now()->addHours(5)->toDateTimeString(),
    ];

    $response = $this->postJson("/api/bookings/{$booking->id}/slots", $slotData);

    $response->assertForbidden();
});

test('пользователь может удалить свое бронирование', function () {
    $booking = $this->user->bookings()->create();
    $slot = $booking->slots()->create([
        'start_time' => now()->addHour(),
        'end_time' => now()->addHours(2),
    ]);

    $response = $this->deleteJson("/api/bookings/{$booking->id}");

    $response->assertOk()
        ->assertJson(['success' => true]);
    
    $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    $this->assertDatabaseMissing('booking_slots', ['id' => $slot->id]);
});

test('пользователь не может удалить чужое бронирование', function () {
    $otherUser = User::factory()->create(['api_token' => 'other_token']);
    $booking = $otherUser->bookings()->create();

    $response = $this->deleteJson("/api/bookings/{$booking->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
});

test('создание бронирования откатывается при ошибке', function () {
    DB::shouldReceive('transaction')->once();
    DB::shouldReceive('insert')->andThrow(new \Exception('Test exception'));

    $slots = [
        [
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
        ]
    ];

    $response = $this->postJson('/api/bookings', [
        'slots' => $slots
    ]);

    $response->assertStatus(500)
        ->assertJsonStructure(['error']);
});

test('запрос без токена отклоняется', function () {
    $this->withHeaders(['Authorization' => '']);

    $response = $this->get('/api/bookings');
    $response->assertStatus(403);
});

test('запрос с неверным токеном отклоняется', function () {
    $this->withHeaders(['Authorization' => 'Bearer invalid_token']);

    $response = $this->get('/api/bookings');
    $response->assertStatus(403);
});

test('пользователь не может получить чужие бронирования', function () {
    $otherUser = User::factory()->create(['api_token' => 'other_token']);
    $booking = $otherUser->bookings()->create();

    $response = $this->get('/api/bookings');
    
    $response->assertOk()
        ->assertJsonCount(0, 'bookings');
});
