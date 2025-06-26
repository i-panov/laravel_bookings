<?php

use App\Http\Requests\StoreSingleSlotRequest;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('app.timezone', 'UTC');
    Config::set('app.minSlotDurationInMinutes', 15);
});

test('валидация проходит для корректного слота', function () {
    $data = [
        'start_time' => now()->addHour()->toDateTimeString(),
        'end_time' => now()->addHours(2)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('не проходит, когда start_time в прошлом', function () {
    $data = [
        'start_time' => now()->subHour()->toDateTimeString(),
        'end_time' => now()->addHour()->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_time'))->toBeTrue();
});

test('не проходит, когда end_time раньше start_time', function () {
    $data = [
        'start_time' => now()->addHours(2)->toDateTimeString(),
        'end_time' => now()->addHour()->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('end_time'))->toBeTrue();
});

test('не проходит, когда слот слишком короткий', function () {
    $data = [
        'start_time' => now()->addHour()->toDateTimeString(),
        'end_time' => now()->addHour()->addMinutes(10)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('end_time'))->toBeTrue()
        ->and($validator->errors()->first('end_time'))->toContain('не менее 15 минут');
});

test('не проходит при пересечении с существующим бронированием', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(3),
    ]);

    $data = [
        'start_time' => now()->addHours(2)->toDateTimeString(),
        'end_time' => now()->addHours(4)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_time'))->toBeTrue()
        ->and($validator->errors()->first('start_time'))->toContain('уже забронирован');
});

test('проходит, когда слот между существующими бронированиями', function () {
    $user = User::factory()->create();
    $booking1 = $user->bookings()->create();
    $booking2 = $user->bookings()->create();
    
    $booking1->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(2),
    ]);
    
    $booking2->slots()->create([
        'start_time' => now()->addHours(3),
        'end_time' => now()->addHours(4),
    ]);

    $data = [
        'start_time' => now()->addHours(2)->addMinutes(30)->toDateTimeString(),
        'end_time' => now()->addHours(2)->addMinutes(45)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('не проходит при точном пересечении с существующим бронированием', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $slot = $booking->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(3),
    ]);

    $data = [
        'start_time' => $slot->start_time->toDateTimeString(),
        'end_time' => $slot->end_time->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue();
});

test('не проходит при частичном пересечении начала бронирования', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(2),
        'end_time' => now()->addHours(4),
    ]);

    $data = [
        'start_time' => now()->addHour()->toDateTimeString(),
        'end_time' => now()->addHours(3)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue();
});

test('не проходит при частичном пересечении конца бронирования', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(3),
    ]);

    $data = [
        'start_time' => now()->addHours(2)->toDateTimeString(),
        'end_time' => now()->addHours(4)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue();
});

test('проходит при смежности с концом бронирования', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(2),
    ]);

    $data = [
        'start_time' => now()->addHours(2)->toDateTimeString(),
        'end_time' => now()->addHours(3)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('проходит при смежности с началом бронирования', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(2),
        'end_time' => now()->addHours(3),
    ]);

    $data = [
        'start_time' => now()->addHour()->toDateTimeString(),
        'end_time' => now()->addHours(2)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('корректно работает с разными часовыми поясами', function () {
    Config::set('app.timezone', 'Europe/Moscow');
    
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->setTimezone('UTC')->addHours(1),
        'end_time' => now()->setTimezone('UTC')->addHours(3),
    ]);

    $data = [
        'start_time' => now()->setTimezone('Europe/Moscow')->addHours(4)->toDateTimeString(),
        'end_time' => now()->setTimezone('Europe/Moscow')->addHours(5)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('не проходит при неверном формате даты', function () {
    $data = [
        'start_time' => 'некорректная-дата',
        'end_time' => now()->addHour()->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_time'))->toBeTrue();
});

test('не проходит для даты в прошлом', function () {
    $data = [
        'start_time' => now()->subDay()->toDateTimeString(),
        'end_time' => now()->subDay()->addHours(2)->toDateTimeString(),
    ];

    $request = new StoreSingleSlotRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_time'))->toBeTrue();
});
