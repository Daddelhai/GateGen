<?php

require_once "include/mysql.php";

class Flight {

    protected $_id;
    protected $_flightnumber;
    protected $_radiocallsign;
    protected $_origin;
    protected $_destination;
    protected $_deptime;
    protected $_arrtime;
    protected $_gate;
    protected $_aircraft_iata;
    protected $_route;
    protected $_vid;
    protected $_bookingtimestamp;
    protected $_bookingstatus;

    protected $_turnover = null;
    protected $_has_turnover = null;

    # CONSTRUCT

    protected function __construct($query_result) {
        $this->_id = $query_result->fetchFirst("id");
        $this->_flightnumber = $query_result->fetchFirst("flightnumber");
        $this->_radiocallsign = $query_result->fetchFirst("radiocallsign");
        $this->_origin = $query_result->fetchFirst("origin");
        $this->_destination = $query_result->fetchFirst("destination");
        $this->_deptime = $query_result->fetchFirst("deptime");
        $this->_arrtime = $query_result->fetchFirst("arrtime");
        $this->_gate = $query_result->fetchFirst("gate");
        $this->_aircraft_iata = $query_result->fetchFirst("acft");
        $this->_route = $query_result->fetchFirst("route");
        $this->_vid = $query_result->fetchFirst("vid");
        $this->_bookingtimestamp = $query_result->fetchFirst("bookingtimestamp");
        $this->_bookingstatus = $query_result->fetchFirst("bookingstatus");
        $this->_turnover_id = $query_result->fetchFirst("turnover");
    }

    public static function FromFlightnumber($flightnumber) {
        global $MYSQL;
        return new self($MYSQL->execute("SELECT `id`, `flightnumber`, `radiocallsign`, `origin`, `destination`, `deptime`, `arrtime`, `gate`, `acft`, `route`, COALESCE(`turnover`,(SELECT `rfe_flights_2`.`id` FROM `rfe_flights` AS `rfe_flights_2` WHERE `rfe_flights_2`.`turnover` = `rfe_flights`.`id`)) AS `turnover`, `vid`, `bookingtimestamp`, `bookingstatus` FROM `rfe_flights` WHERE `flightnumber` = '$flightnumber'"));
    }

    public static function FromID($id) {
        global $MYSQL;
        return new self($MYSQL->execute("SELECT `id`, `flightnumber`, `radiocallsign`, `origin`, `destination`, `deptime`, `arrtime`, `gate`, `acft`, `route`, COALESCE(`turnover`,(SELECT `rfe_flights_2`.`id` FROM `rfe_flights` AS `rfe_flights_2` WHERE `rfe_flights_2`.`turnover` = `rfe_flights`.`id`)) AS `turnover`, `vid`, `bookingtimestamp`, `bookingstatus` FROM `rfe_flights` WHERE `id` = '$id'"));
    }

    public function clear_cache() {
        global $MYSQL;
        $query_result = $MYSQL->execute("SELECT `id`, `flightnumber`, `radiocallsign`, `origin`, `destination`, `deptime`, `arrtime`, `gate`, `acft`, `route`, COALESCE(`turnover`,(SELECT `rfe_flights_2`.`id` FROM `rfe_flights` AS `rfe_flights_2` WHERE `rfe_flights_2`.`turnover` = `rfe_flights`.`id`)) AS `turnover`, `vid`, `bookingtimestamp`, `bookingstatus` FROM `rfe_flights` WHERE `id` = '$this->_id'");

        $this->_id = $query_result->fetchFirst("id");
        $this->_flightnumber = $query_result->fetchFirst("flightnumber");
        $this->_radiocallsign = $query_result->fetchFirst("radiocallsign");
        $this->_origin = $query_result->fetchFirst("origin");
        $this->_destination = $query_result->fetchFirst("destination");
        $this->_deptime = $query_result->fetchFirst("deptime");
        $this->_arrtime = $query_result->fetchFirst("arrtime");
        $this->_gate = $query_result->fetchFirst("gate");
        $this->_aircraft_iata = $query_result->fetchFirst("acft");
        $this->_route = $query_result->fetchFirst("route");
        $this->_vid = $query_result->fetchFirst("vid");
        $this->_bookingtimestamp = $query_result->fetchFirst("bookingtimestamp");
        $this->_bookingstatus = $query_result->fetchFirst("bookingstatus");
        $this->_turnover_id = $query_result->fetchFirst("turnover");
    }

    # GETTER

    public function id() {
        return $this->_id;
    }

    public function flightnumber() {
        return $this->_flightnumber;
    }

    public function radiocallsign() {
        return $this->_radiocallsign;
    }

    public function origin() {
        return $this->_origin;
    }

    public function destination() {
        return $this->_destination;
    }

    public function deptime() {
        return $this->_deptime;
    }

    public function arrtime() {
        return $this->_arrtime;
    }

    public function gate() {
        return $this->_gate;
    }

    public function stand() {
        return $this->_gate;
    }

    public function aircraft() {
        return $this->_aircraft_iata;
    }

    public function route() {
        return $this->_route;
    }

    public function vid() {
        return $this->_vid;
    }

    public function bookingtimestamp() {
        return $this->_bookingtimestamp;
    }

    public function bookingstatus() {
        return $this->_bookingstatus;
    }

    # SETTER

    public function assign_stand(Stand $stand, $last_turnover = null) {
        global $MYSQL;

        $id = $stand->identifier();

        $MYSQL->execute("UPDATE `rfe_flights` SET `gate`='$id' WHERE `id`='{$this->_id}' ")->await();
        $this->_stand = $id;

        if ($this->has_turnover())
        {
            $turnover = $this->turnover();
            if ($last_turnover === null || $last_turnover != $turnover->id())
                $turnover->assign_stand($stand, $this->_id);
        }

        echo "Setted {$this->_radiocallsign} to {$stand->identifier()} \n";
    }


    # TURNOVER

    private function __get_increased_flightnumber($flightnumber)
    {
        return preg_replace_callback("/^([A-Z]{2,3})([0-9]+)$/",function($results){
            return $results[1].($results[2]+1);
        },$flightnumber);        
    }

    private function __get_decreased_flightnumber($flightnumber)
    {
        return preg_replace_callback("/^([A-Z]{2,3})([0-9]+)$/",function($results){
            return $results[1].($results[2]-1);
        },$flightnumber);
    }
    
    protected function _get_turnover()
    {
        global $MYSQL;

        if ($this->_turnover_id == null) {

            $incr_flightnumber = $this->__get_increased_flightnumber($this->_flightnumber);
            $decr_flightnumber = $this->__get_increased_flightnumber($this->_flightnumber);

            $calculated_turnover = $MYSQL->execute("SELECT `id` FROM `rfe_flights` WHERE `destination`='{$this->_origin}' AND `origin`='{$this->_destination}' AND `arrtime` IS NULL AND `acft`='{$this->_aircraft_iata}' AND (`flightnumber` LIKE '$incr_flightnumber' OR `flightnumber` LIKE '$decr_flightnumber')");

            if ($calculated_turnover->rows()) {
                $turnover_id = $calculated_turnover->fetchFirst("id");

                $this->_has_turnover = true;
                $this->_turnover = Flight::FromID($turnover_id);

                $MYSQL->execute("UPDATE `rfe_flights` SET `turnover`='$turnover_id' WHERE `id` = '{$this->_id}'")->await();
                return;
            }

        } else {
            $this->_has_turnover = true;
            $this->_turnover = Flight::FromID($this->_turnover_id);
            return;
        }

        $this->_has_turnover = false;
        $this->_turnover = null;
        return;
    }

    public function turnover(): Flight {
        if ($this->_has_turnover === null) $this->_get_turnover();
        return $this->_turnover;
    }

    public function has_turnover(): bool {
        if ($this->_has_turnover === null) $this->_get_turnover();
        return $this->_has_turnover;
    }

}