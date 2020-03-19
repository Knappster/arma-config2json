<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use Config2Json\FileParser;

$file_path = "./custom.vars.Arma3Profile";
$file_parser = new FileParser();
$file_parsed = $file_parser->parse($file_path);

header('Content-type: application/json');
echo $file_parsed;
