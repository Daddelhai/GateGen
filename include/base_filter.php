<?php

require_once "include/cls_flight.php";
require_once "include/cls_stand.php";

interface BaseFilter  {
    public function __construct(Flight $flight, String $eventlocation);
    public function validate(Stand $stand): bool;
}


function export_filter($cls)
{
    global $_FILTER;
    $_FILTER[] = $cls;
}