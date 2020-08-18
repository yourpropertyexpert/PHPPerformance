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
