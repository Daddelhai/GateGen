<?php

require_once "include/cls_stand.php";

interface BaseParser {
    public function __construct(array $flightData);
    public function next();
}

function export_parser(String $key, $cls)
{
    global $_PARSER;
    if (isset($_PARSER[$key])) 
        throw new Exception("Parser on '$key' does already exist");
    $_PARSER[$key] = $cls;
}

function get_stand($stand_identifier)
{
    global $_STANDS;

    return $_STANDS[$stand_identifier];
}