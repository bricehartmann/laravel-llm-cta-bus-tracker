<?php

namespace App\Enums;

enum BusDirection: string
{
    case NORTH_BOUND = 'NB';
    case SOUTH_BOUND = 'SB';
    case EAST_BOUND = 'EB';
    case WEST_BOUND = 'WB';
    case NORTHEAST_BOUND = 'NEB';
    case NORTHWEST_BOUND = 'NWB';
    case SOUTHEAST_BOUND = 'SEB';
    case SOUTHWEST_BOUND = 'SWB';
}
