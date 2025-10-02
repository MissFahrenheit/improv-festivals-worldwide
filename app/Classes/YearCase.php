<?php

namespace App\Classes;

enum YearCase
{
    case ALL_EMPTY;
    case CURRENT_YEAR_ONLY;
    case NEXT_YEAR_ONLY;
    case BOTH_YEARS_EXIST;
}
