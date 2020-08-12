<?php

function loopMeUnparameterised()
{
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

function loopMeParameterised($count)
{
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
