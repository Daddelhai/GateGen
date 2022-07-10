<?php

require_once "include/base_filter.php";

class AptStartWithFilter implements BaseFilter
{
    private $__flight;

    public function __construct($flight, $eventlocation)
    {
        $this->__flight = $flight;
        $this->__eventlocation = $eventlocation;
    }

    public function validate($stand): bool
    {
        if (empty($stand["Filter"]["RefAerodromStartswith"])) return true;

        $is_arrival = $this->__flight->destination() == $this->__eventlocation;
        $airport = $is_arrival ? $this->__flight->origin() : $this->__flight->destination();

        foreach ($stand["Filter"]["RefAerodromStartswith"] as $allowed_aerodrome) 
        {
            if (self::__startsWith($airport,$allowed_aerodrome)) return true;
        }
        return false;
    }

    private static function __startsWith($string, $needle)
    {
        return substr($string, 0, strlen($needle)) == $needle;
    }
}

export_filter(AptStartWithFilter::class);