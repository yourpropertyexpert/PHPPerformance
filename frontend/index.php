<?php

const ITERATIONS = 10000;

echo '<h1>PHP Performance tester</h1>';

echo '<p>The purpose of this code is to see how fast (relative to each other)
different ways of "doing something" are in PHP. The code is deliberately ultra-lightweight,
with no frameworks or classes</p>';
echo '<p>The "something" is generating a set of random numbers, and summing them</p>';
echo "<p>Running ".ITERATIONS." iterations of each loop. You can change this number be modifying the
constant at the top of frontend/index.php</p>";

echo '<table><thead><th>Method</th><th>Time</th><th>Factor</th></thead><tbody>';

// The simple loop
$starttime = microtime(true);
$i = 0;
$n = 0;
while ($i < ITERATIONS) {
    $n = $n+rand();
    $i++;
}
$totalLoop = round(microtime(true) - $starttime, 5);
echo "<tr><td>Running a simple loop</td><td>$totalLoop ms</td><td>1</td></tr>";

$secondLoop = $totalLoop * 2;
echo "<tr><td>Running a difficult loop</td><td>$secondLoop ms</td><td>2</td></tr>";


echo '</tbody></table>';
