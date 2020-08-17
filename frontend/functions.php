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

function showResultRow($technique, $time, $base)
{
    echo "<tr><td>$technique</td>";
    echo '<td>', round($time, PRECISION), ' s</td><td>', round($time / $base, PRECISION), "</td></tr>\n";
    flush();
}
