<?php

namespace App\Enums;

enum SeatStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case Reserved = 'reserved';
    case Blocked = 'blocked';
    case Selected = 'selected';
}