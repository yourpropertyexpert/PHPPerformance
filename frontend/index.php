<?php

const ITERATIONS = 500;
const PRECISION = 5;
const NUMBERFORMAT = 8;

require_once '/var/www/vendor/autoload.php';

include 'classes.php';
include 'functions.php';

// We do NOT use a Template builder
// This is because we want to keep flushing the output after each test type

echo <<< PAGE_TOP
<!DOCTYPE html>
<html lang="en-gb">
<head>
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <style type='text/css'>
    #resultsTable th, #resultsTable td { text-align: right; }
    #resultsTable th:first-child, #resultsTable td:first-child { text-align: left; }
  </style>
</head>
<body>
<h1>PHP Performance tester</h1>
<p>The purpose of this code is to see how fast (relative to each other)
different ways of “doing something” are in PHP. The code is deliberately ultra-lightweight,
with no frameworks or classes.</p>
<p>The “something” is generating a set of random numbers, and summing them.</p>
<p><i>Pre-seeding the various data structures, so that all we are seeing is the output times...</i></p>

PAGE_TOP;
flush();

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
$myclass = new MHL\Demo($Iterations);

// $_SERVER['SCRIPT_URI'] is set by mod_rewrite, but not otherwise.
if (empty($_SERVER['SCRIPT_URI'])) {
    $_SERVER['SCRIPT_URI'] = (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')) ? 'https' : 'http';
    $_SERVER['SCRIPT_URI'] .= "://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]";
}

echo '<p><i>Running ', number_format($Iterations), " iterations of each loop. You can change this number by ";
if (strpos($_SERVER['QUERY_STRING'], 'I=') === false) {
    echo "adding an I= parameter to this page (eg $_SERVER[SCRIPT_URI]?I=1234)";
} else {
    echo "changing the value of the I= parameter to this page";
}
echo ", or by modifying the constant at the top of frontend/index.php</i></p>";
flush();

$starttime = microtime(true);
$i = 0;
$n = 0;
while ($i < $Iterations) {
    $n = $n + rand();
    $i++;
}
$totalLoop = microtime(true) - $starttime;

echo '<h2>Results</h2>';

echo '<table id="resultsTable"><thead><th>Method</th><th>Time</th><th>Factor</th></thead><tbody>';

showResultRow('Page: Simple loop', $totalLoop);

$unparamtime = loopMeUnparameterised();
showResultRow('Page: Unparameterised local function', $unparamtime);

$paramtime = loopMeParameterised($Iterations);
showResultRow('Page: Parameterised local function', $paramtime);

$starttime = microtime(true);
$n = $myclass->getN($Iterations);
$classGetN = microtime(true) - $starttime;
showResultRow('Class: Single method call that did the iteration in the method', $classGetN);

$starttime = microtime(true);
$i = 0;
while ($i < $Iterations) {
    $myclass->getN(1);
    $i++;
}
$classGet1 = microtime(true) - $starttime;
showResultRow('Class: Call the method multiple times from a loop in the calling page', $classGet1);

$starttime = microtime(true);
$n = $myclass->getNFromMemcached($Iterations);
$classGetNFromMemcached = microtime(true) - $starttime;
showResultRow(
    'External: Single method call, that ran a loop calling class shared memcached each time',
    $classGetNFromMemcached
);

$starttime = microtime(true);
$n = $myclass->getNFromRedis($Iterations);
$classGetNFromRedis = round(microtime(true) - $starttime, PRECISION);
showResultRow(
    'External: Single method call, that ran a loop calling class shared Redis each time',
    $classGetNFromRedis
);

$starttime = microtime(true);
$n = $myclass->getNFromDBQuery($Iterations);
$classgetNFromDBQuery = round(microtime(true) - $starttime, PRECISION);
showResultRow(
    'External: Single method call, that ran a loop calling a new SQL query each time',
    $classgetNFromDBQuery
);

$starttime = microtime(true);
$n = $myclass->getNFromDBQueryInOneGo($Iterations);
$classgetNFromDBQueryInOneGo = round(microtime(true) - $starttime, PRECISION);
showResultRow(
    'External: Single method call, that ran one SQL query then looped over the returned data',
    $classgetNFromDBQueryInOneGo
);

$starttime = microtime(true);
$n = $myclass->getNFromAPI($Iterations);
$classGetNFromAPI = round(microtime(true) - $starttime, PRECISION);
showResultRow(
    'External: Single method call, that ran a loop calling class shared API each time',
    $classGetNFromAPI
);

$times = ['totalLoop', 'unparamtime', 'paramtime', 'classGetN', 'classGet1',
          'classGetNFromMemcached', 'classGetNFromRedis',
          'classgetNFromDBQuery', 'classgetNFromDBQueryInOneGo', 'classGetNFromAPI'];
foreach ($times as $var) {
    $$var = number_format($$var, NUMBERFORMAT);
}

echo <<< PAGE_END
</tbody></table>

<div id='container' style='width:100%; height:400px;'></div>
<div id='container2' style='width:100%; height:400px;'></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
        var myChart = Highcharts.chart('container', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Timings in seconds - Logarithmic axis'
            },
            xAxis: {
                categories: [
                    'Simple loop',
                    'Local unparameterised function',
                    'Local parameterised function',
                    'Loop inside a single method call',
                    'Method called once per iteration',
                    'Memcached',
                    'Redis',
                    'SQL (n queries)',
                    'SQL (one query)',
                    'API',
                ]
            },
            yAxis: {
                type: 'logarithmic',
                minorTickInterval: 'auto'
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: false
                    }
                }
            },
            series: [{
                name: 'On-page looping',
                data: [
                    $totalLoop,
                    $unparamtime,
                    $paramtime,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                ]
            },
            {
                name: 'Class-based looping',
                data: [
                    0,
                    0,
                    0,
                    $classGetN,
                    $classGet1,
                    0,
                    0,
                    0,
                    0,
                    0
                ]
            },
            {
                name: 'External data source',
                data: [
                    0,
                    0,
                    0,
                    0,
                    0,
                    $classGetNFromMemcached,
                    $classGetNFromRedis,
                    $classgetNFromDBQuery,
                    $classgetNFromDBQueryInOneGo,
                    $classGetNFromAPI
                ]
            }
        ]
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
            var myChart = Highcharts.chart('container2', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Timings in seconds - Linear axis'
                },
                xAxis: {
                    categories: [
                        'Simple loop',
                        'Local unparameterised function',
                        'Local parameterised function',
                        'Loop inside a single method call',
                        'Method called once per iteration',
                        'Memcached',
                        'Redis',
                        'SQL (n queries)',
                        'SQL (one query)',
                        'API',
                    ]
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        dataLabels: {
                            enabled: false
                        }
                    }
                },
                series: [{
                    name: 'On-page looping',
                    data: [
                        $totalLoop,
                        $unparamtime,
                        $paramtime,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ]
                },
                {
                    name: 'Class-based looping',
                    data: [
                        0,
                        0,
                        0,
                        $classGetN,
                        $classGet1,
                        0,
                        0,
                        0,
                        0,
                        0
                    ]
                },
                {
                    name: 'External data source',
                    data: [
                        0,
                        0,
                        0,
                        0,
                        0,
                        $classGetNFromMemcached,
                        $classGetNFromRedis,
                        $classgetNFromDBQuery,
                        $classgetNFromDBQueryInOneGo,
                        $classGetNFromAPI
                    ]
                }
            ]
            });
        });
</script>
</body>
</html>

PAGE_END;
