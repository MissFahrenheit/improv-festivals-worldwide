<?php

namespace App\Classes;

enum YearCase
{
    case ALL_EMPTY;
    case CURRENT_YEAR_EXISTS;
    case NEXT_YEAR_EXISTS;
    case BOTH_YEARS_EXIST;
}
