<?php

require_once "include/base_filter.php";

class AirlineFilter implements BaseFilter
{
    private $__flight;

    public function __construct($flight, $eventlocation)
    {
        $this->__flight = $flight;
        $this->__eventlocation = $eventlocation;
    }

    public function validate($stand): bool
    {
        if (empty($stand["Filter"]["Airline"])) return true;

        $airline_icao = substr($this->__flight->radiocallsign(),0,3);

        foreach ($stand["Filter"]["Airline"] as $allowed_airline) 
        {
            if ($airline_icao == $allowed_airline) return true;
        }
        return false;
    }
}

export_filter(AirlineFilter::class);