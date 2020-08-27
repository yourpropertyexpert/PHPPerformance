<?php

require '/var/www/vendor/autoload.php';

const INFO_URI = 'https://genericserver.link/information';  // the URI from which we fetch info
const HTTP_OK = 200;                                        // HTTP code for "OK"
const VERSION_SUBSTR = 8;                                   // How much of the version string to display

$mloader = new Mustache_Loader_FilesystemLoader("$_SERVER[DOCUMENT_ROOT]/templates");
$mustache = new Mustache_Engine(['loader' => $mloader]);

// Mustache data
$mustacheData = ['error' => '', 'versions' => []];

try {
    $Guz = new \GuzzleHttp\Client();
    $response = $Guz->post(INFO_URI);
    if ($response->getStatusCode() == HTTP_OK) {
        try {
            $data = json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);
            if (is_array($data) && isset($data['OK'])) {
                if ($data['OK']) {
                    // We have useful stats, in $data['Stats']. That's an array whose keys are version strings, and
                    // whose values are the data we want in the table, so we'll do a bit of re-laying-out to get it
                    // into a format to drop into the template
                    foreach ($data['Stats'] as $version => $info) {
                        $mustacheData['versions'][] = array_merge(
                            ['Version' => substr($version, 0, VERSION_SUBSTR) . '…',
                             'Incomplete' => number_format($info['Sessions'] - $info['Complete'])],
                            array_map('number_format', $info)
                        );
                    }
                } else {
                    $mustacheData['error'] = "Statistics error $data[Error]";
                }
            } else {
                $mustacheData['error'] = 'Data format error';
            }
        } catch (\Exception $ex) {
            $mustacheData['error'] = 'Data decode error ' . $ex->getMessage();
        }
    } else {
        $mustacheData['error'] = 'HTTP error ' . $response->getReasonPhrase();
    }
} catch (\Exception $ex) {
    $mustacheData['error'] = 'Data decode error ' . $ex->getMessage();
}

echo $mustache->render('info', $mustacheData);
