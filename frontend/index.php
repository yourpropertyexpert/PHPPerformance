<?php

const ITERATIONS = 500;
const PRECISION = 5;
const NUMBERFORMAT = 8;

require_once '/var/www/vendor/autoload.php';

include 'classes.php';
include 'functions.php';

// We do NOT use a Template builder
// This is because we want to keep flushing the output after each test type

echo '<head><script src="https://code.highcharts.com/highcharts.js"></script></head>';

echo '<h1>PHP Performance tester</h1>';

echo '<p>The purpose of this code is to see how fast (relative to each other)
different ways of "doing something" are in PHP. The code is deliberately ultra-lightweight,
with no frameworks or classes</p>';
echo '<p>The "something" is generating a set of random numbers, and summing them</p>';
echo '<p><i>Pre-seeding the various data structures, so that all we are seeing is the output times...</i></p>';
flush();
$myclass = new MHL\Demo(ITERATIONS);

echo "<p><i>Running ".ITERATIONS." iterations of each loop. You can change this number be modifying the
constant at the top of frontend/index.php</i></p>";
flush();
$starttime = microtime(true);
$i = 0;
$n = 0;
while ($i < ITERATIONS) {
    $n = $n+rand();
    $i++;
}
$totalLoop = round(microtime(true) - $starttime, PRECISION);

echo '<h2>Results</h2>';

echo '<table><thead><th>Method</th><th>Time</th><th>Factor</th></thead><tbody>';

echo "<tr><td>Page: Simple loop</td>";
echo "<td>$totalLoop s</td><td>1</td></tr>";
flush();

$unparamtime = loopMeUnparameterised();
$paramtime = loopMeParameterised(ITERATIONS);


$starttime = microtime(true);
$n = $myclass->getN(ITERATIONS);
$classGetN = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>Class - Single method call that did the iteration in the method</td>";
echo "<td>$classGetN s</td><td>".round($classGetN / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$i = 0;
while ($i < ITERATIONS) {
    $myclass->getN(1);
    $i++;
}
$classGet1 = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>Class - Call the method multiple times from a loop in the calling page</td>";
echo "<td>$classGet1 s</td><td>".round($classGet1 / $totalLoop, PRECISION)."</td></tr>";
flush();




$starttime = microtime(true);
$n = $myclass->getNFromMemcached(ITERATIONS);
$classGetNFromMemcached = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>External - Single method call, that ran a loop calling class shared memcached each time</td>";
echo "<td>$classGetNFromMemcached s</td><td>".round($classGetNFromMemcached / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$n = $myclass->getNFromRedis(ITERATIONS);
$classGetNFromRedis = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>External - Single method call, that ran a loop calling class shared Redis each time</td>";
echo "<td>$classGetNFromRedis s</td><td>".round($classGetNFromRedis / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$n = $myclass->getNFromDBQuery(ITERATIONS);
$classgetNFromDBQuery = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>External - Single method call, that ran a loop calling a new SQL query each time</td>";
echo "<td>$classgetNFromDBQuery s</td><td>".round($classgetNFromDBQuery / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$n = $myclass->getNFromAPI(ITERATIONS);
$classGetNFromAPI = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>External - Single method call, that ran a loop calling class shared API each time</td>";
echo "<td>$classGetNFromAPI s</td><td>".round($classGetNFromAPI / $totalLoop, PRECISION)."</td></tr>";
flush();


echo '</tbody></table>';


echo "<div id='container' style='width:100%; height:400px;'></div>";
echo "<div id='container2' style='width:100%; height:400px;'></div>";
echo "<script>
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
                    'SQL',
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
                    ".number_format($totalLoop, NUMBERFORMAT).",
                    ".number_format($unparamtime, NUMBERFORMAT).",
                    ".number_format($paramtime, NUMBERFORMAT).",
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
                    ".number_format($classGetN, NUMBERFORMAT).",
                    ".number_format($classGet1, NUMBERFORMAT).",
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
                    $classGetNFromAPI
                ]
            }
        ]
        });
    });
    </script>";
    echo "<script>
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
                        'SQL',
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
                        ".number_format($totalLoop, NUMBERFORMAT).",
                        ".number_format($unparamtime, NUMBERFORMAT).",
                        ".number_format($paramtime, NUMBERFORMAT).",
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
                        ".number_format($classGetN, NUMBERFORMAT).",
                        ".number_format($classGet1, NUMBERFORMAT).",
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
                        $classGetNFromAPI
                    ]
                }
            ]
            });
        });
        </script>";
