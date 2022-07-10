<?php

require_once "globals.php";
require_once "include/cls_flight.php";

foreach (scandir("parser") as $file) if (is_file("parser/$file")) {
    include_once "parser/$file";
}
foreach (scandir("filter") as $file) if (is_file("filter/$file")) {
    include_once "filter/$file";
}

function main($skip_assigned = false) {
    ignore_user_abort();
    
    if (!$skip_assigned)
    {
        global $MYSQL;
        $promise = $MYSQL->execute("UPDATE `rfe_flights` SET `gate`= NULL",MYSQL_ASYNC);
    }

    $event_airport = get_event_airport();

    $all_flights = get_all_flights();

    $all_stands = generate_all_stands($event_airport);
    
    sort_stands($all_stands);

    if (!$skip_assigned)
    {
        $promise->await();
    }
    
    generate($all_flights, $all_stands, $event_airport);
}

function sort_stands(&$all_stands) {
    usort($all_stands, function ($lhs, $rhs) { return $rhs->priority() <=> $lhs->priority(); });
}

// EVENT AIRPORT

function get_event_airport() {
    global $MYSQL;

    return $MYSQL->execute("SELECT `apticao` FROM `rfe_config`")->fetchFirst("apticao");
}

// PARSE STANDS


function generate_all_stands($event_airport) {
    if (is_file("aerodromes/".strtolower($event_airport).".json"))
    {
        $content = json_decode(file_get_contents("aerodromes/".strtolower($event_airport).".json"),true);

        if ($content["Aerodrome"] == $event_airport)
            return parse_stands($content);
    }

    foreach (scandir("aerodromes") as $file) if (is_file("aerodromes/$file")) {
        $content = json_decode(file_get_contents("aerodromes/$file"),true);

        if ($content["Aerodrome"] == $event_airport)
            return parse_stands($content);
    }

    throw new Exception("Event Aerodrom has no configuration file!");
}

function parse_stands($content)
{
    global $_PARSER, $_STANDS;

    foreach ($_PARSER as $key => $parser)
    {
        if (isset($content[$key]))
        {
            $parser = new $parser($content[$key]);

            while(null !== $stand = $parser->next())
            {
                $_STANDS[] = $stand;
            }
        }
    }

    return $_STANDS;
}

// PARSE FLIGHTS

function get_all_flights()
{
    global $MYSQL;
    $flights = array();

    $query = $MYSQL->execute("SELECT `id` FROM `rfe_flights`");
    foreach ($query->fetchAll() as $row)
    {
        $flights[] = Flight::FromID($row['id']);
    }

    return $flights;
}

// GENERATOR

function generate($flights, $stands, $eventlocation)
{
    foreach ($flights as $flight)
    {
        $flight->clear_cache();

        if ($flight->stand() != null) continue;
            
        $filter = get_filter($flight, $eventlocation);
        
        foreach ($stands as $stand) {
            if (check_filter($filter, $stand))
            {
                $flight->assign_stand($stand);
                break;
            }
        }
    }
}

function get_filter($flight, $eventlocation)
{
    global $_FILTER;
    $all_filter = array();

    foreach($_FILTER as $filter)
    {
        $all_filter[] = new $filter($flight, $eventlocation);
    }

    return $all_filter;
}

function check_filter($filter, $stand)
{
    foreach ($filter as $f) 
        if ( !$f->validate($stand) ) 
            return false;
    return true;
}