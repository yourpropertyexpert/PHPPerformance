<?php

function loopMeUnparameterised()
{
    $Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
    $starttime = microtime(true);
    $n = 0;
    $i = 0;
    while ($i < $Iterations) {
        $n = $n+rand();
        $i++;
    }
    return microtime(true) - $starttime;
}

function loopMeParameterised($count)
{
    $starttime = microtime(true);
    $n = 0;
    $i = 0;
    while ($i < $count) {
        $n = $n+rand();
        $i++;
    }
    return microtime(true) - $starttime;
}

function showResultRow($technique, $time)
{
    global $totalLoop;
    echo "<tr><td>$technique</td>";
    echo '<td>', round($time, PRECISION), ' s</td><td>', round($time / $totalLoop, PRECISION), "</td></tr>\n";
    flush();
}
