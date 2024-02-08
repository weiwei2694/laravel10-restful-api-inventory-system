<?php

namespace App\Helpers;

class DateHelper
{
    public static function formatDateTime($datetime)
    {
        return $datetime->format('Y-m-d\TH:i:s.u\Z');
    }
}
