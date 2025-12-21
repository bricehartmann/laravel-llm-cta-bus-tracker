<?php

namespace App\Enums;

enum BusStopPosition: string
{
    case FAR_SIDE_OF_INTERSECTION = 'FS';
    case NEAR_SIDE_OF_INTERSECTION = 'NS';
    case NEAR_SIDE_OF_T_INTERSECTION = 'NT';
    case MIDDLE_OF_BLOCK = 'MB';
    case MIDDLE_OF_T_INTERSECTION = 'MT';
    case FAR_SIDE_OF_T_INTERSECTION = 'FT';
    case TERMINAL = 'TERM';
}
