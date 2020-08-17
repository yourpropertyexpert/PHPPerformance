<?php

function loopMeParameterised($count)
{
    $n = 0;
    $i = 0;
    while ($i < $count) {
        $n = $n + rand();
        $i++;
    }
    return $n;
}

function showResultRow($technique, $time)
{
    global $totalLoop;
    echo "<tr><td>$technique</td>";
    echo '<td>', round($time, PRECISION), ' s</td><td>', round($time / $totalLoop, PRECISION), "</td></tr>\n";
    flush();
}
