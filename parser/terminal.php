<?php

require_once "include/base_parser.php";

/*
        {
            "Name":"Terminal 1",
            "Priority": 100,
            "Description":"Schengen",
            "Filter": {
                "Schengen": 1,
                "RefAerodromStartswith": [""],
                "Airline": [""],
                "Cat": ["c"]
            },
            "Stands": ["1","2","3","4","5","8","9"]
        }
*/

class TerminalParser implements BaseParser
{
    private $__json;
    private $__iter = 0;
    private $__inner_iter = 0;

    public function __construct($json) {
        $this->__json = $json;
    }

    public function next()
    {
        $terminal = $this->__json[$this->__iter];

        if (!isset($terminal["Stands"][$this->__inner_iter]))
        {
            $this->__inner_iter = 0;
            ++$this->__iter;
            if (!isset($this->__json[$this->__iter]))
                return null;
            $terminal = $this->__json[$this->__iter];
        }

        $stand = new Stand($terminal["Stands"][$this->__inner_iter], $terminal["Priority"], $terminal["Name"]);
        $stand["Terminal"] = $terminal["Name"];
        $stand["Description"] = $terminal["Description"];
        $stand["Filter"] = $terminal["Filter"];
        
        ++$this->__inner_iter;

        return $stand;
    }
}

export_parser("Terminals", TerminalParser::class);