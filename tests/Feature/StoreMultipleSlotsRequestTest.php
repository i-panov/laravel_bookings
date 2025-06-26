<?php

use App\Http\Requests\StoreMultipleSlotsRequest;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('app.timezone', 'UTC');
    Config::set('app.minSlotDurationInMinutes', 15);
});

test('валидация проходит для корректных слотов', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->addHour()->toDateTimeString(),
                'end_time' => now()->addHours(2)->toDateTimeString(),
            ],
            [
                'start_time' => now()->addHours(3)->toDateTimeString(),
                'end_time' => now()->addHours(4)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('не проходит при пустом массиве слотов', function () {
    $data = ['slots' => []];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots'))->toBeTrue();
});

test('не проходит, когда start_time в прошлом', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->subHour()->toDateTimeString(),
                'end_time' => now()->addHour()->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0.start_time'))->toBeTrue();
});

test('не проходит, когда end_time раньше start_time', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->addHours(2)->toDateTimeString(),
                'end_time' => now()->addHour()->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0.end_time'))->toBeTrue();
});

test('не проходит, когда слот слишком короткий', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->addHour()->toDateTimeString(),
                'end_time' => now()->addHour()->addMinutes(10)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0.end_time'))->toBeTrue()
        ->and($validator->errors()->first('slots.0.end_time'))->toContain('не менее 15 минут');
});

test('не проходит при пересечении с существующим бронированием', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(3),
    ]);

    $data = [
        'slots' => [
            [
                'start_time' => now()->addHours(2)->toDateTimeString(),
                'end_time' => now()->addHours(4)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0'))->toBeTrue()
        ->and($validator->errors()->first('slots.0'))->toContain('пересекается с существующим бронированием');
});

test('проходит, когда слоты между существующими бронированиями', function () {
    $user = User::factory()->create();
    $booking = $user->bookings()->create();
    
    $booking->slots()->create([
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(2),
    ]);
    
    $booking->slots()->create([
        'start_time' => now()->addHours(3),
        'end_time' => now()->addHours(4),
    ]);

    $data = [
        'slots' => [
            [
                'start_time' => now()->addHours(2)->addMinutes(30)->toDateTimeString(),
                'end_time' => now()->addHours(2)->addMinutes(45)->toDateTimeString(),
            ],
            [
                'start_time' => now()->addHours(4)->addMinutes(30)->toDateTimeString(),
                'end_time' => now()->addHours(4)->addMinutes(45)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('не проходит при внутренних пересечениях слотов', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->addHours(1)->toDateTimeString(),
                'end_time' => now()->addHours(3)->toDateTimeString(),
            ],
            [
                'start_time' => now()->addHours(2)->toDateTimeString(),
                'end_time' => now()->addHours(4)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0'))->toBeTrue()
        ->and($validator->errors()->has('slots.1'))->toBeTrue()
        ->and($validator->errors()->first('slots.0'))->toContain('другим слотом в запросе')
        ->and($validator->errors()->first('slots.1'))->toContain('другим слотом в запросе');
});

test('не проходит при частичных внутренних пересечениях', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->addHours(1)->toDateTimeString(),
                'end_time' => now()->addHours(2)->toDateTimeString(),
            ],
            [
                'start_time' => now()->addHours(1)->addMinutes(30)->toDateTimeString(),
                'end_time' => now()->addHours(2)->addMinutes(30)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0'))->toBeTrue()
        ->and($validator->errors()->has('slots.1'))->toBeTrue();
});

test('проходит при смежных слотах без пересечений', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->addHours(1)->toDateTimeString(),
                'end_time' => now()->addHours(2)->toDateTimeString(),
            ],
            [
                'start_time' => now()->addHours(2)->toDateTimeString(),
                'end_time' => now()->addHours(3)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
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
        'slots' => [
            [
                'start_time' => now()->setTimezone('Europe/Moscow')->addHours(4)->toDateTimeString(),
                'end_time' => now()->setTimezone('Europe/Moscow')->addHours(5)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('не проходит при неверном формате даты', function () {
    $data = [
        'slots' => [
            [
                'start_time' => 'некорректная-дата',
                'end_time' => now()->addHour()->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0.start_time'))->toBeTrue();
});

test('не проходит для даты в прошлом', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->subDay()->toDateTimeString(),
                'end_time' => now()->subDay()->addHours(2)->toDateTimeString(),
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0.start_time'))->toBeTrue();
});

test('не проходит при нескольких типах ошибок', function () {
    $data = [
        'slots' => [
            [
                'start_time' => now()->subHour()->toDateTimeString(), // прошлое
                'end_time' => now()->addHour()->toDateTimeString(),
            ],
            [
                'start_time' => now()->addHours(3)->toDateTimeString(),
                'end_time' => now()->addHours(2)->toDateTimeString(), // end раньше start
            ]
        ]
    ];

    $request = new StoreMultipleSlotsRequest($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slots.0.start_time'))->toBeTrue()
        ->and($validator->errors()->has('slots.1.end_time'))->toBeTrue();
});
