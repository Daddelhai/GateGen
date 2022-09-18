<?php

global $MYSQL;

abstract class DIRECTION {
    const ARRIVAL = 0;
    const DEPARTURE = 1;
}

$flights = [];

$query = $MYSQL->exec("SELECT `id`,`gate`,`arrtime`,`deptime`,`radiocallsign` FROM `rfe_flights` WHERE `Gate` IS NOT NULL ORDER BY `Gate` ASC, IFNULL(`arrtime`,`deptime`)ASC");

$lastgate = "";


$lastarr = null;

echo "<grid class=\"gateoverview\">";

$min = PHP_INT_MAX;
$max = 0;
foreach ($query->fetchAll() as $row) {
    $t = $row['arrtime'];
    if (!$row['arrtime']) {
        $t = $row['deptime'];
    }
    $t = strtotime($t);
    if ($t < $min) $min = $t;
    if ($t > $max) $max = $t;
}
$max = substr($max-$min,0,-2)+5;

$r = 2;

foreach ($query->fetchAll() as $row)
{
    $gate = $row['gate'];
    if ($lastgate != $gate)
    {
        if ($lastarr !== null) {
            echo "<div class=\"occupancy\" style=\"grid-column:$lastarr/$max;grid-row:$r\" cs=\"$cs\"><div class=\"cs\">$cs</div></div>";
        }

        $lastgate = $gate;
        ++$r;
        echo "<div class=\"devider\" style=\"grid-column:1/$max;grid-row:$r\"></div>";
        ++$r;
        echo "<div class=\"gate\" style=\"grid-column:1;grid-row:$r\">$gate</div>";

        $lastarr = null;
    }

    $direction = DIRECTION::ARRIVAL;
    $t = $row['arrtime'];
    if (!$row['arrtime']) {
        $t = $row['deptime'];
        $direction = DIRECTION::DEPARTURE;
    }
    $col = substr( strtotime($t) - $min , 0, -2) + 3;

    $cs = $row['radiocallsign'];

    if ($direction == DIRECTION::DEPARTURE)
    {
        if ($lastarr === null) {
            echo "<div class=\"occupancy\" style=\"grid-column:2/$col;grid-row:$r\" cs=\"$cs\"><div class=\"cs\">$cs</div></div>";
        }
        else
        {
            echo "<div class=\"occupancy\" style=\"grid-column:$lastarr/$col;grid-row:$r\" cs=\"$cs\" cs2=\"$lastarr_cs\"><div class=\"cs\">$lastarr_cs<br>$cs</div></div>";
        } 
        $lastarr = null;

        echo "<div class=\"flight dep\" style=\"grid-column:$col;grid-row:$r\" title=\"Departure: $cs\" cs=\"$cs\"></div>";
    }
    elseif ($direction == DIRECTION::ARRIVAL)
    {
        $lastarr = $col + 1;
        $lastarr_cs = $cs;

        echo "<div class=\"flight arr\" style=\"grid-column:$col;grid-row:$r\" title=\"Arrival: $cs\" cs=\"$cs\"></div>";
    }    
}
if ($lastarr !== null) {
    echo "<div class=\"occupancy\" style=\"grid-column:$lastarr/$max;grid-row:$r\" cs=\"$cs\"><div class=\"cs\">$cs</div></div>";
}
++$r;

$timecol = 3;
echo "<div style=\"grid-column:1;grid-row:1;height:20px\"></div>";
while($timecol < $max-10) {
    $time = date('Hi\Z',$min + ($timecol-3)*100);
    echo "<div class=\"time\" style=\"grid-column:$timecol;grid-row:1/$r\">$time</div>";
    $timecol += 18;
}

echo "</grid>";