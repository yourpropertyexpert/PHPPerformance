<?php

const ITERATIONS = 5000;
const PRECISION = 5;

require_once '/var/www/vendor/autoload.php';

include 'classes.php';

// We do NOT use a Template builder
// This is because we want to keep flushing the output after each test type

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

echo "<tr><td>Simple loop</td>";
echo "<td>$totalLoop s</td><td>1</td></tr>";
flush();

loopMeUnparameterised();
loopMeParameterised(ITERATIONS);


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
echo "<tr><td>Class - Single method call, that ran a loop calling class shared memcached each time</td>";
echo "<td>$classGetNFromMemcached s</td><td>".round($classGetNFromMemcached / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$n = $myclass->getNFromRedis(ITERATIONS);
$classGetNFromRedis = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>Class - Single method call, that ran a loop calling class shared Redis each time</td>";
echo "<td>$classGetNFromRedis s</td><td>".round($classGetNFromRedis / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$n = $myclass->getNFromDBQuery(ITERATIONS);
$classgetNFromDBQuery = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>Class - Single method call, that ran a loop calling a new SQL query each time</td>";
echo "<td>$classgetNFromDBQuery s</td><td>".round($classgetNFromDBQuery / $totalLoop, PRECISION)."</td></tr>";
flush();

$starttime = microtime(true);
$n = $myclass->getNFromAPI(ITERATIONS);
$classGetNFromAPI = round(microtime(true) - $starttime, PRECISION);
echo "<tr><td>Class - Single method call, that ran a loop calling class shared API each time</td>";
echo "<td>$classGetNFromAPI s</td><td>".round($classGetNFromAPI / $totalLoop, PRECISION)."</td></tr>";
flush();

echo '</tbody></table>';
echo '<p>Done</p>';

function loopMeUnparameterised() {
    $starttime = microtime(true);
    $n = 0;
    $i = 0;
    while ($i < ITERATIONS) {
        $n = $n+rand();
        $i++;
    }
    $unparameterisedFunction = round(microtime(true) - $starttime, PRECISION);
    echo "<tr><td>Unparameterised local function</td>";
    echo "<td>$unparameterisedFunction s</td><td>1</td></tr>";
    flush();
}

function loopMeParameterised($count) {
    $starttime = microtime(true);
    $n = 0;
    $i = 0;
    while ($i < $count) {
        $n = $n+rand();
        $i++;
    }
    $unparameterisedFunction = round(microtime(true) - $starttime, PRECISION);
    echo "<tr><td>Parameterised local function</td>";
    echo "<td>$unparameterisedFunction s</td><td>1</td></tr>";
    flush();
}
