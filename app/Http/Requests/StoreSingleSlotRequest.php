<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\BookingSlot;
use Illuminate\Support\Carbon;

/**
 * @property string $start_time
 * @property string $end_time
 */
class StoreSingleSlotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ];
    }
    
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            
            $data = $validator->getData();
            $timezone = config('app.timezone');
            
            try {
                $start = Carbon::parse($data['start_time'])->shiftTimezone($timezone);
            } catch (\Exception $e) {
                $validator->errors()->add('start_time', 'Неверный формат даты');
                return;
            }

            try {
                $end = Carbon::parse($data['end_time'])->shiftTimezone($timezone);
            } catch (\Exception $e) {
                $validator->errors()->add('end_time', 'Неверный формат даты');
                return;
            }

            $minSlotDuration = (int) config('app.minSlotDurationInMinutes');

            if ($minSlotDuration > 0 && $start->diffInMinutes($end) < $minSlotDuration) {
                $validator->errors()->add('end_time', "Слот должен быть не менее $minSlotDuration минут");
                return;
            }

            $exists = BookingSlot::query()
                ->where(function ($query) use ($start, $end) {
                    $query->where('start_time', '<', $end->utc())
                          ->where('end_time', '>', $start->utc());
                })
                ->exists();
                
            if ($exists) {
                $validator->errors()->add('start_time', 'Данный временной слот уже забронирован');
            }
        });
    }
}
