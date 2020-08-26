<?php

/**
 * Ajax endpoint, invoked to run each test and return a result.
 *
 * This script handles three functions, depending on the parameters passed in (either as GET or POST).
 *  Given a numeric Setup parameter, we're initialising - the parameter is the number of times each test
 *      will be run; create an instance of the handler class and store it in the PHP session for future use.
 *  Given a truthy Teardown parameter, we've finished and should tidy up.
 *  Given a numberic Way parameter, we're running one of the test "ways", uploading the result to the central
 *      server if required, and returning the restults of this test, and the consolidated results from the
 *      server, as a JSON-encoded structure.
 * Only the Way method has any concrete return (the other two return an empty JSON-encoded structure). Not
 * passing any of the parameters will not result in an error here, but nothing will be returned, which should
 * cause an error at the caller, assumning it's expecting a JSON return. Making the calls out of order will
 * provoke a string error message (which again will cause a failure at the caller)
 */

require_once '/var/www/vendor/autoload.php';

require_once 'classes.php';
require_once 'functions.php';
require_once 'ways.php';
require_once 'ajax_handler.php';

// We're always responding with JSON, or at least we say we are!
header('Content-Type: application/json');

session_start();

if (!empty($_REQUEST['Setup']) && is_numeric($_REQUEST['Setup'])) {
    // Setup - create and store a class instance, unless we already have one
    if (isset($_SESSION['Class'])) {
        die('Functions called out of order');
    }
    $_SESSION['Class'] = new PRHL\PHPPerformance\AjaxHandler($_REQUEST['Setup'], !empty($_REQUEST['Upload']));
    echo '{}';  // empty JSON object
} elseif (!empty($_REQUEST['Teardown'])) {
    // Tidy up
    if (!isset($_SESSION['Class']) || !($_SESSION['Class'] instanceof PRHL\PHPPerformance\AjaxHandler)) {
        die('Functions called out of order');
    }
    $_SESSION['Class']->teardown();
    unset($_SESSION['Class']);
    echo '{}';  // empty JSON object
} elseif (isset($_REQUEST['Way']) && is_numeric($_REQUEST['Way'])) {
    // Run one of our Ways, assuming it exists, and return the result
    if (!isset($_SESSION['Class']) || !($_SESSION['Class'] instanceof PRHL\PHPPerformance\AjaxHandler)) {
        die('Functions called out of order');
    }
    echo $_SESSION['Class']->run((int)$_REQUEST['Way']);
}
