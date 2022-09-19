<?php

# Debug Mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "../../phpinc/func_general.php";

# Fallback values. Do not change! Use 'phpinc/func_general.php' instead.

if (!defined('cookie_name')) define('cookie_name', 'rfe_token');
if (!defined('login_url')) define('login_url', 'https://login.ivao.aero/index.php');
if (!defined('api_url')) define('api_url', 'https://login.ivao.aero/api.php');
if (!defined('url')) define('url', 'https://rfe.ch.ivao.aero/');

# MySQL

$ini = parse_ini_file("../../phpinc/data.ini.php");

define ("MYSQL_HOST",$ini["host"]);
define ("MYSQL_DB",$ini["rfedatabase"]);
define ("MYSQL_NAVDB",$ini["navdatabase"]);
define ("MYSQL_USER",$ini["login_db"]);
define ("MYSQL_PASSWORD",$ini["pass_db"]);
define ("MYSQL_PORT",$ini["port"]);

# Check login

global $IVAOUSR;

if(isset($_COOKIE[cookie_name])) {
    $IVAOUSR = json_decode(file_get_contents(api_url.'?type=json&token='.$_COOKIE[cookie_name]),true);
    if (!$IVAOUSR['staff']) {
        http_response_code(403);
        die();
    }
} else {
    http_response_code(403);
    die();
}



# globals
global $_FILTER, $_PARSER, $_STANDS;
$_FILTER = $_PARSER = $_STANDS = [];