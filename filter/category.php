<?php

require_once "include/base_filter.php";

class CategoryFilter implements BaseFilter
{
    private $__flight;

    public function __construct($flight, $eventlocation)
    {
        $this->__flight = $flight;
        $this->__eventlocation = $eventlocation;
    }

    public function validate($stand): bool
    {
        if (empty($stand["Filter"]["Cat"])) return true;

        global $MYSQL;
        $aircraft = $this->__flight->aircraft();
        $aircraft_cat = $MYSQL->exec("SELECT `cat` FROM `nav_aircrafts` WHERE `iata`='$aircraft'")->fetchFirst("cat");

        foreach ($stand["Filter"]["Cat"] as $allowed_cat) 
        {
            if ($aircraft_cat == $allowed_cat) return true;
        }
        return false;
    }

    private static function __startsWith($string, $needle)
    {
        return substr($string, 0, strlen($needle)) == $needle;
    }
}

export_filter(CategoryFilter::class);