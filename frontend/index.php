<?php

const ITERATIONS = 10000;

echo '<h1>PHP Performance tester</h1>';

echo '<p>The purpose of this code is to see how fast (relative to each other)
different ways of "doing something" are in PHP. The code is deliberately ultra-lightweight,
with no frameworks or classes</p>';
echo '<p>The "something" is generating a set of random numbers, and summing them</p>';
echo "<p>Running ".ITERATIONS." iterations of each loop. You can change this number be modifying the
constant at the top of frontend/index.php</p>";

// The simple loop
$starttime = microtime(true);
$i = 0;
$n = 0;
while ($i < ITERATIONS) {
    $n = $n+rand();
    $i++;
}
$totalLoop = round(microtime(true) - $starttime, 5);
echo "<p>Running a simple loop took $totalLoop ms</p>";
