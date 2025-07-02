<?php

namespace App\DTO;

class SlotData {
    public function __construct(
        public readonly string $start_time,
        public readonly string $end_time
    ) {}
}
