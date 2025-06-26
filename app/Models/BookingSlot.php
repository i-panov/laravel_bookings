<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $booking_id
 * @property $start_time
 * @property $end_time
 * @property Booking $booking
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Booking|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingSlot whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BookingSlot extends Model
{
    protected $fillable = ['booking_id', 'start_time', 'end_time'];

    protected $casts = [
        'id' => 'int',
        'booking_id' => 'int',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(Booking::class);
    }
}
