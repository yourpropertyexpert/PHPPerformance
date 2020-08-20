<?php

const ITERATIONS = 10;
const SERIES_NAMES = ['On-page looping', 'Class-based null looping', 'Class-based looping with payload', 'External data source'];

require '/var/www/vendor/autoload.php';
include_once 'ways.php';


$mloader = new Mustache_Loader_FilesystemLoader($_SERVER['DOCUMENT_ROOT'] . '/templates');
$mustache = new Mustache_Engine(['loader' => $mloader]);

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];

// Our series definitions for the chart (lots of arrays of zeros!)
$series = [];
foreach (SERIES_NAMES as $i => $name) {
    $series[] = ['name' => $name, 'data' => array_fill(0, count(Ways(null, 'Index')), 0)];
}

// Mustache data
$data = [
         'iterations' => $Iterations,
         'displayIterations' => number_format($Iterations),
         'ways' => Ways(null, 'IndexTable'),
         'linear' => (empty($_REQUEST['axis']) || ($_REQUEST['axis'] != 'lin')),
         'captions' => Ways(null, 'Graph'),
         'series' => json_encode($series, JSON_PRETTY_PRINT),
        ];

echo $mustache->render('page', $data);
