<?php

require_once '/var/www/vendor/autoload.php';

require_once 'classes.php';
require_once 'functions.php';
require_once 'ways.php';

const PRECISION = 5;

// We're always responding with JSON
header('Content-Type: application/json');

session_start();

// We need to be told what to do - either a numeric Way= parameter, a numeric Setup=
// (specifying the number of iterations) or a truthy Teardown= one
if (!empty($_REQUEST['Setup']) && is_numeric($_REQUEST['Setup'])) {
    // Setup - create and store a class instance
    $_SESSION['Iterations'] = (int)$_REQUEST['Setup'];
    $_SESSION['Class'] = new MHL\Demo($_SESSION['Iterations']);
    $_SESSION['Times'] = [];
    echo '{}';  // empty JSON object
} elseif (!empty($_REQUEST['Teardown'])) {
    // Clean up our database references
    $_SESSION['Class']->cleanup();
    unset($_SESSION['Class']);
    echo '{}';  // empty JSON object
} elseif (isset($_REQUEST['Way']) && is_numeric($_REQUEST['Way'])) {
    // Run one of our Ways, assuming it exists
    $index = (int)$_REQUEST['Way'];
    try {
        $way = Ways($index);
    } catch (Exception $ex) {
        echo '{}';  // return an empty object if this Way doesn't exist
        return;     // and that's all there is to do
    }

    if ($index == 0) {
        // handle the basic loop separately
        $starttime = microtime(true);
        for ($i = $n = 0; $i < $_SESSION['Iterations']; ++$i) {
            $n += rand();
        }
        $_SESSION['Times'][$index] = microtime(true) - $starttime;
    } elseif ($way['Loop']) {
        if ($way['Class']) {
            $starttime = microtime(true);
            for ($i = $n = 0; $i < $_SESSION['Iterations']; ++$i) {
                $n += call_user_func([$_SESSION['Class'], $way['Function']], 1);
            }
            $_SESSION['Times'][$index] = microtime(true) - $starttime;
        } else {
            $starttime = microtime(true);
            for ($i = $n = 0; $i < $_SESSION['Iterations']; ++$i) {
                $n += call_user_func($way['Function'], 1);
            }
            $_SESSION['Times'][$index] = microtime(true) - $starttime;
        }
    } else {
        if ($way['Class']) {
            $n = 0;
            $starttime = microtime(true);
            $n += call_user_func([$_SESSION['Class'], $way['Function']], $_SESSION['Iterations']);
            $_SESSION['Times'][$index] = microtime(true) - $starttime;
        } else {
            $n = 0;
            $starttime = microtime(true);
            $n += call_user_func($way['Function'], $_SESSION['Iterations']);
            $_SESSION['Times'][$index] = microtime(true) - $starttime;
        }
    }
    // We need the set of chart points for the series this one belongs to
    $points = array_fill(0, count(Ways(null, 'Index')), 0);
    foreach ($_SESSION['Times'] as $index => $time) {
        if (Ways($index, 'Series') == $way['Series']) {
            $points[$index] = $time;
        }
    }


    // and send back a result object
    echo json_encode([
        'index' => $index,
        'time' => round($_SESSION['Times'][$index], PRECISION),
        'factor' => round($_SESSION['Times'][$index] / $_SESSION['Times'][0], PRECISION),
        'series' => $way['Series'],
        'points' => $points,
        ]);
}
