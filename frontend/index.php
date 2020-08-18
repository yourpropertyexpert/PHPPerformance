<?php

const ITERATIONS = 10;
const SERIES_NAMES = ['On-page looping', 'Class-based looping', 'External data source'];

require '/var/www/vendor/autoload.php';
include_once 'ways.php';


$mloader = new Mustache_Loader_FilesystemLoader($_SERVER['DOCUMENT_ROOT'] . '/templates');
$mustache = new Mustache_Engine(['loader' => $mloader]);
$mustachedata = [];

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
$data["iterations"] = $Iterations;
$data["displayIterations"] = number_format($Iterations);

$data["ways"] = [];
foreach (Ways() as $index => $way) {
    $thisway = [];
    $thisway["index"] = $index;
    $thisway["description"] = $way["Table"];
    $data["ways"][] = $thisway;
}

if (empty($_REQUEST['axis']) || ($_REQUEST['axis'] != 'lin')) { // log axis, default
    $data["linear"] = true;
} else {
    $data["linear"] = false;
}

$data["captions"] = [];
foreach (Ways(null, 'Graph') as $caption) {
    $data["captions"][] = $caption;
}
echo $mustache->render("top", $data);

$json = [];
foreach (SERIES_NAMES as $i => $name) {
    $json[] = ['name' => $name, 'data' => array_fill(0, count(Ways(null, 'Index')), 0)];
}
echo '        series: ', json_encode($json, JSON_PRETTY_PRINT), ",\n";


echo $mustache->render("bottom", $data);
