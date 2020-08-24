<?php

require_once '/var/www/vendor/autoload.php';

require_once 'classes.php';
require_once 'functions.php';
require_once 'ways.php';

const PRECISION = 5;        // decimal places in returned results
const HTTP_OK = 200;        // HTTP code for "OK"
const MAGIC_LENGTH = 16;    // Length of our "magic number" (bytes)

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
    // We store most of the parameters for uploading, too
    $_SESSION['Version'] = md5_file('ways.php');
    $_SESSION['Magic'] = (empty($_REQUEST['Upload'])) ? '' : bin2hex(openssl_random_pseudo_bytes(MAGIC_LENGTH));
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

    // Upload (we make the call even if we're not uploading, because we want the result)
    $data = ['Magic' => $_SESSION['Magic'],
             'Version' => $_SESSION['Version'],
             'Iterations' => $_SESSION['Iterations'],
             'Way' => $index,
            ];
    if ($_SESSION['Magic']) {
        $data['Result'] = $_SESSION['Times'][$index];   // only send the result if we're uploading
    }
    $Guz = new GuzzleHttp\Client();
    try {
        $response = $Guz->post('https://genericserver.link/mothership', [ 'form_params' => $data ]);
        if ($response->getStatusCode() == HTTP_OK) {  // HTTP OK
            try {
                $consolidated = json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);
                if (!is_array($consolidated) || !isset($consolidated['OK'])) {
                    $consolidated = ['OK' => false, 'Error' => 'Data format error'];
                }
            } catch (\Exception $ex) {
                $consolidated = ['OK' => false, 'Error' => 'Data decode error ' . $ex->getMessage()];
            }
        } else {
            $consolidated = [ 'OK' => false, 'Error' => 'HTTP error ' . $response->getReasonPhrase() ];
        }
    } catch (\Exception $ex) {
        $consolidated = ['OK' => false, 'Error' => 'Data upload error ' . $ex->getMessage()];
    }

    // and send back a result object
    echo json_encode([
        'index' => $index,
        'time' => round($_SESSION['Times'][$index], PRECISION),
        'factor' => round($_SESSION['Times'][$index] / $_SESSION['Times'][0], PRECISION),
        'series' => $way['Series'],
        'points' => $points,
        'consolidated' => $consolidated,
        ]);
}
