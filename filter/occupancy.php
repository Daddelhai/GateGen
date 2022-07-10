<?php

require_once "include/base_filter.php";

class OccupancyFilter implements BaseFilter
{
    private $__flight;
    private $__eventlocation;

    public function __construct(Flight $flight, $eventlocation)
    {
        $this->__flight = $flight;
        $this->__eventlocation = $eventlocation;
    }

    public function validate(Stand $stand): bool
    {
        global $MYSQL;

        $is_arrival = $this->__flight->destination() == $this->__eventlocation;
        $has_turnover = $this->__flight->has_turnover();

        $flight_time = $is_arrival ? $this->__flight->arrtime() : $this->__flight->deptime();
        $gate = $stand->identifier();

        $last_dep = $MYSQL->execute("SELECT `deptime`,`radiocallsign` FROM `rfe_flights` WHERE `Gate`='$gate' AND `deptime` IS NOT NULL AND `deptime` <= TIME('$flight_time') ORDER BY ABS( TIMEDIFF( `deptime`, TIME('$flight_time') ) ) LIMIT 1");
        $last_arr = $MYSQL->execute("SELECT `arrtime`,`radiocallsign` FROM `rfe_flights` WHERE `Gate`='$gate' AND `arrtime` IS NOT NULL AND `arrtime` <= TIME('$flight_time') ORDER BY ABS( TIMEDIFF( `arrtime`, TIME('$flight_time') ) ) LIMIT 1");
        
        $next_dep = $MYSQL->execute("SELECT `deptime`,`radiocallsign` FROM `rfe_flights` WHERE `Gate`='$gate' AND `deptime` IS NOT NULL AND `deptime` >= TIME('$flight_time') ORDER BY ABS( TIMEDIFF( `deptime`, TIME('$flight_time') ) ) LIMIT 1");
        $next_arr = $MYSQL->execute("SELECT `arrtime`,`radiocallsign` FROM `rfe_flights` WHERE `Gate`='$gate' AND `arrtime` IS NOT NULL AND `arrtime` >= TIME('$flight_time') ORDER BY ABS( TIMEDIFF( `arrtime`, TIME('$flight_time') ) ) LIMIT 1");
        
        if ($last_dep->rows() == 0 && $last_arr->rows() == 1) return false;

        if ($last_dep->rows() == 1 && $last_arr->rows() == 0 && !$is_arrival) return false;
        
        if ($next_dep->rows() == 1 && $next_arr->rows() == 0) return false;

        if ($last_dep->rows() == 1 && $last_arr->rows() == 1)
        {
            if (strtotime($last_arr->fetchFirst("arrtime")) >= strtotime($last_dep->fetchFirst("deptime"))) 
            {
                if ($is_arrival) return false;
                if (substr($last_arr->fetchFirst("radiocallsign"),0,3) != substr($this->__flight->radiocallsign(),0,3)) return false; 
                if ($last_arr->fetchFirst("arrtime") + (45*60) > $flight_time) return false;
            }
            if (strtotime($last_arr->fetchFirst("arrtime")) < strtotime($last_dep->fetchFirst("deptime")))
            {
                if (!$is_arrival) return false;
            }
        }

        if ($next_arr->rows() == 1)
        {
            if (!$has_turnover) return false;

            if ($is_arrival) {
                $turnover_dep = $this->__flight->turnover()->deptime();
                assert($turnover_dep !== null, "Arrival turnover must be a departure with departure time.");

                if (strtotime($next_arr->fetchFirst("arrtime")) <= $turnover_dep) return false;
            } 
        }

        return true;
    }
}

export_filter(OccupancyFilter::class);