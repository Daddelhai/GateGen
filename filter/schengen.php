<?php

require_once "include/base_filter.php";

class SchengenFilter implements BaseFilter
{
    private $__flight;
    private $__eventlocation;

    private const __SCHENGEN_ICAO__ = ["BI","EB","ED","EE","EF","EH","EI","EK","EL","EN","EP","ES","ET","EV","EY","LB","LD","LE","LF","LG","LH","LI","LJ","LK","LM","LN","LO","LP","LR","LS","LX","LZ"];


    public function __construct($flight, $eventlocation)
    {
        $this->__flight = $flight;
        $this->__eventlocation = $eventlocation;
    }

    public function validate($stand): bool
    {
        if ($stand["Filter"]["Schengen"] == 0) return true;
        
        $is_arrival = $this->__flight->destination() == $this->__eventlocation;
        $airport = $is_arrival ? $this->__flight->origin() : $this->__flight->destination();

        if ($stand["Filter"]["Schengen"] == 1) 
        {
            foreach (self::__SCHENGEN_ICAO__ as $schengen_icao)
            {
                if (self::__startsWith($airport,$schengen_icao)) return true;
            }
            return false;
        }
        else
        {
            foreach (self::__SCHENGEN_ICAO__ as $schengen_icao)
            {
                if (self::__startsWith($airport,$schengen_icao)) return false;
            }
            return true;
        }
    }

    private static function __startsWith($string, $needle)
    {
        return substr($string, 0, strlen($needle)) == $needle;
    }
}

export_filter(SchengenFilter::class);