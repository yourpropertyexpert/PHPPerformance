<?php

const ITERATIONS = 10;
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
  <link rel="stylesheet"
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh"
    crossorigin="anonymous">

</head>
<body>
<div class="container">
<div class="jumbotron">
<h1>PHP Performance tester</h1>
<p>The purpose of this code is to see how fast (relative to each other)
different ways of “doing something” are in PHP. The code is deliberately ultra-lightweight,
with no frameworks or classes.</p>
<p>The “something” is generating a set of random numbers, and summing them.</p>
</div>
<div class="container">
<div class='alert alert-success'>
    Pre-seeding the various data structures, so that all we are seeing is the output times...
</div>

PAGE_TOP;
flush();

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
$myclass = new MHL\Demo($Iterations);

// $_SERVER['SCRIPT_URI'] is set by mod_rewrite, but not otherwise.
if (empty($_SERVER['SCRIPT_URI'])) {
    $_SERVER['SCRIPT_URI'] = (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off')) ? 'https' : 'http';
    $_SERVER['SCRIPT_URI'] .= "://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]";
}

echo '<div class="alert alert-success">Running ', number_format($Iterations), " iterations of each loop.</div>";
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

echo '<table class="table table-striped" id="resultsTable">
    <thead><th>Method</th><th>Time</th><th>Factor</th></thead>
    <tbody>';

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
    'External: Single method call, that ran a loop calling a new MySQL query each time',
    $classgetNFromDBQuery
);

$starttime = microtime(true);
$n = $myclass->getNFromDBQueryInOneGo($Iterations);
$classgetNFromDBQueryInOneGo = round(microtime(true) - $starttime, PRECISION);
showResultRow(
    'External: Single method call, that ran one MySQL query then looped over the returned data',
    $classgetNFromDBQueryInOneGo
);

$starttime = microtime(true);
$n = $myclass->getNFromSQLite($Iterations);
$classgetNFromSQLite = round(microtime(true) - $starttime, PRECISION);
showResultRow(
    'External: Single method call, that ran a loop calling a new SQLite query each time',
    $classgetNFromSQLite
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
          'classgetNFromDBQuery', 'classgetNFromDBQueryInOneGo', 'classgetNFromSQLite',
          'classGetNFromAPI'];
foreach ($times as $var) {
    $$var = number_format($$var, NUMBERFORMAT);
}

echo <<< PAGE_END
</tbody></table>

<h2>Run again</h2>
<form method='post'>
  <label for="I">Number of iterations</label><br>
  <input type="text" id="fname" name="I" value="$Iterations">
  <input type="submit" name="submit">
</form>

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
                    'MySQL (n queries)',
                    'MySQL (one query)',
                    'SQLite',
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
                    $classgetNFromSQLite,
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
                    'MySQL (n queries)',
                    'MySQL (one query)',
                    'SQLite',
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
                    $classgetNFromSQLite,
                    $classGetNFromAPI
                ]
            }
        ]
        });
    });
</script>
</div>
</div>
</body>
</html>



PAGE_END;
