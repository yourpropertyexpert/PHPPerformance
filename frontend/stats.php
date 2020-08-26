<?php

require '/var/www/vendor/autoload.php';
include_once 'ways.php';

const STATS_URI = 'https://genericserver.link/stats'; // the URI from which stats are fetched
const HTTP_OK = 200;        // HTTP code for "OK"

$mloader = new Mustache_Loader_FilesystemLoader("$_SERVER[DOCUMENT_ROOT]/templates");
$mustache = new Mustache_Engine(['loader' => $mloader]);

// Mustache data
$mustacheData = ['error' => '', 'series' => ''];

try {
    $Guz = new \GuzzleHttp\Client();
    $response = $Guz->post(STATS_URI, [ 'form_params' => [ 'VersionOrder' => -1 ] ]);
    if ($response->getStatusCode() == HTTP_OK) {
        try {
            $chart = [];
            $data = json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);
            if (is_array($data) && isset($data['OK'])) {
                if ($data['OK']) {
                    // We have useful stats, in $data['Stats']. The first level is the version, and we've only asked
                    // for one, so discard that
                    $stats = (array_values($data['Stats']))[0];
                    // Then we can work through the list of Ways. If we don't have any data, we skip that one,
                    // and we skip Way #0 anyway
                    foreach (Ways(null, 'Graph') as $index => $caption) {
                        if ($index == 0 || !isset($stats[$index])) {
                            continue;
                        }
                        $series = ['name' => $caption, 'data' => []];
                        // Now work through the list of iteration-counts for this way
                        foreach ($stats[$index] as $iterations => $results) {
                            // We need the relative time, so we can only use a result
                            // if we also have the corresponding result for way #0
                            if (isset($stats[0][$iterations]) && !empty($stats[0][$iterations]['Time'])) {
                                $series['data'][] = [$iterations, $results['Time'] / $stats[0][$iterations]['Time'] ];
                            }
                        }
                        $chart[] = $series;
                    }
                    $mustacheData['series'] = json_encode($chart, JSON_NUMERIC_CHECK);
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

if ($mustacheData['error']) {
    $mustacheData['error'] = "<div class='alert alert-danger'>$mustacheData[error]</div>";
}

echo $mustache->render('stats', $mustacheData);
