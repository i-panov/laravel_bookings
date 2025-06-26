<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\BookingSlot;
use Illuminate\Support\Carbon;

class StoreMultipleSlotsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'slots' => 'required|array|min:1',
            'slots.*.start_time' => 'required|date|after:now',
            'slots.*.end_time' => 'required|date|after:slots.*.start_time',
        ];
    }
    
    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            
            $data = $validator->getData();
            $timezone = config('app.timezone');
            $carbonSlots = [];
            $hasErrors = false;

            foreach ($data['slots'] as $index => $slot) {
                try {
                    $start = Carbon::parse($slot['start_time'])->shiftTimezone($timezone);
                } catch (\Exception $e) {
                    $validator->errors()->add("slots.$index.start_time", 'Неверный формат даты');
                    $hasErrors = true;
                    continue;
                }

                try {
                    $end = Carbon::parse($slot['end_time'])->shiftTimezone($timezone);
                } catch (\Exception $e) {
                    $validator->errors()->add("slots.$index.end_time", 'Неверный формат даты');
                    $hasErrors = true;
                    continue;
                }

                $minSlotDuration = config('app.minSlotDurationInMinutes');

                if ($minSlotDuration > 0 && $start->diffInMinutes($end) < $minSlotDuration) {
                    $validator->errors()->add("slots.$index.end_time", "Слот должен быть не менее $minSlotDuration минут");
                    $hasErrors = true;
                    continue;
                }

                $carbonSlots[$index] = [
                    'start' => $start,
                    'end' => $end
                ];
            }
            
            if ($hasErrors || empty($carbonSlots)) {
                return;
            }
            
            $internalErrors = $this->findInternalOverlaps($carbonSlots);
            foreach ($internalErrors as $index => $error) {
                $validator->errors()->add("slots.{$index}", $error);
            }
            
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            
            $existingErrors = $this->findConflictsWithExisting($carbonSlots);
            foreach ($existingErrors as $index => $error) {
                $validator->errors()->add("slots.{$index}", $error);
            }
        });
    }
    
    protected function findInternalOverlaps(array $slots): array
    {
        $errors = [];
        $sortedSlots = collect($slots)->sortBy('start')->values()->all();
        $prevEnd = null;
        $prevIndex = null;

        foreach ($sortedSlots as $index => $slot) {
            if ($prevEnd && $slot['start'] < $prevEnd) {
                $errors[$index] = 'Пересекается с другим слотом в запросе';
                if ($prevIndex !== null) {
                    $errors[$prevIndex] = 'Пересекается с другим слотом в запросе';
                }
            }
            
            $prevEnd = $slot['end'];
            $prevIndex = $index;
        }
        
        return $errors;
    }
    
    protected function findConflictsWithExisting(array $slots): array
    {
        $query = BookingSlot::query();
        $errors = [];
        
        $query->where(function ($q) use ($slots) {
            foreach ($slots as $slot) {
                $q->orWhere(function ($inner) use ($slot) {
                    $inner->where('start_time', '<', $slot['end']->utc())
                          ->where('end_time', '>', $slot['start']->utc());
                });
            }
        });
        
        $conflictingSlots = $query->get();
        
        foreach ($slots as $index => $slot) {
            foreach ($conflictingSlots as $conflicting) {
                if ($slot['start']->lt($conflicting->end_time) && 
                    $slot['end']->gt($conflicting->start_time)) {
                    $errors[$index] = 'Слот пересекается с существующим бронированием';
                    break;
                }
            }
        }
        
        return $errors;
    }
}
