<?php

/**
 * Class that encapsulates the work of the Ajax endpoints.
 *
 * We handle three endpoints from one Ajax file, depending on the parameters passed:
 *  Setup creates an instance of this class and stores it in the PHP session
 *  Way runs one of the test "ways" and returns the result, and the consolidated results
 *  Teardown tidies up
 *
 * @author Patrick Heesom <Patrick@PRHL.uk>
 */

namespace PRHL\PHPPerformance;

class AjaxHandler
{
    private const PRECISION = 5;        // decimal places in returned results
    private const HTTP_OK = 200;        // HTTP code for "OK"
    private const MAGIC_LENGTH = 16;    // Length of our "magic number" (bytes)

    /**
     * @var int $Iterations The number of iterations of each test that will be run
     */
    private $Iterations;
    /**
     * @var \MHL\Demo $Worker An instance of the class that actually runs the tests
     */
    private $Worker;
    /**
     * @var double[] $Times The result of each test in this run
     */
    private $Times;
    /**
     * @var string $Version An opaque string representing the version of the calling code in the uploaded results
     */
    private $Version;
    /**
     * @var string $Magic An opaque string generated at random used to associate the results of the tests in the
     *  same run in the uploaded results, or an empty string if the results will not be stored
     */
    private $Magic;

    /**
     * Constructor : Create our object, including in particular an instace of the Demo class which does the work.
     *
     * In practice, this will be stored in the PHP session between calls (everything here should serialise OK,
     * the Demo class handles its own serialisation.
     *
     * @param int $Iterations The number of iterations of each test that will be run
     * @param bool $Upload True if the reuslts of the tests should be uploaded
     */
    public function __construct($Iterations, $Upload)
    {
        $this->Iterations = (int)$Iterations;
        $this->Worker = new \MHL\Demo($this->Iterations);
        $this->Times = [];
        $this->Version = md5_file('ways.php');
        $this->Magic = ($Upload) ? bin2hex(openssl_random_pseudo_bytes(self::MAGIC_LENGTH)) : '';
    }

    /**
     * teardown : Tidy up at the end of the run.
     *
     * Tear down the object, by instructing the worker object to clean up the database connections, and then remove
     * that object (this can't be done in a destructor, because that would be called every time this object goes out
     * of scope, ie at the end of each separate invocation)
     *
     * @return null
     */
    public function teardown()
    {
        $this->Worker->cleanup();
        unset($this->Worker);
    }

    /**
     * run : Run a specified test and return the result.
     *
     * This is the main function that does the work - given a test, specified by its index, run it, upload the results
     * (if we have permission) and return the result of the test and the consolidated result for this test.
     *
     * @param int $index The index of the test to run
     * @return string The JSON-encoded result of the test (an empty object if the specified test doesn't exist)
     */
    public function run($index)
    {
        try {
            $way = Ways($index);
        } catch (Exception $ex) {
            return '{}';  // return an empty object if this Way doesn't exist
        }

        if ($index == 0) {
            // handle the basic loop separately
            $starttime = microtime(true);
            for ($i = $n = 0; $i < $this->Iterations; ++$i) {
                $n += rand();
            }
            $this->Times[$index] = microtime(true) - $starttime;
        } elseif ($way['Loop']) {
            if ($way['Class']) {
                $starttime = microtime(true);
                for ($i = $n = 0; $i < $this->Iterations; ++$i) {
                    $n += call_user_func([$this->Worker, $way['Function']], 1);
                }
                $this->Times[$index] = microtime(true) - $starttime;
            } else {
                $starttime = microtime(true);
                for ($i = $n = 0; $i < $this->Iterations; ++$i) {
                    $n += call_user_func($way['Function'], 1);
                }
                $this->Times[$index] = microtime(true) - $starttime;
            }
        } else {
            if ($way['Class']) {
                $n = 0;
                $starttime = microtime(true);
                $n += call_user_func([$this->Worker, $way['Function']], $this->Iterations);
                $this->Times[$index] = microtime(true) - $starttime;
            } else {
                $n = 0;
                $starttime = microtime(true);
                $n += call_user_func($way['Function'], $this->Iterations);
                $this->Times[$index] = microtime(true) - $starttime;
            }
        }
        // We need the set of chart points for the series this one belongs to
        $points = array_fill(0, count(Ways(null, 'Index')), 0);
        foreach ($this->Times as $index => $time) {
            if (Ways($index, 'Series') == $way['Series']) {
                $points[$index] = $time;
            }
        }

        // and return back a result object, doing the upload in passing
        return json_encode([
            'index' => $index,
            'time' => round($this->Times[$index], self::PRECISION),
            'factor' => round($this->Times[$index] / $this->Times[0], self::PRECISION),
            'series' => $way['Series'],
            'points' => $points,
            'consolidated' => $this->upload($index),
            ]);
    }

    /**
     * upload : Make the upload call and return the consolidated result.
     *
     * We make an upload call for every test, even if we're not uploading a result, because we want the consolidated
     * data which will be returned.
     *
     * @param int $index The index of the test whose results are being uploaded
     * @return array The return structure, either as returned from the central server or on error, created here in the
     *  same format.
     */
    private function upload($index)
    {
        $data = ['Magic' => $this->Magic,
                 'Version' => $this->Version,
                 'Iterations' => $this->Iterations,
                 'Way' => $index,
                ];
        if ($this->Magic) {
            $data['Result'] = $this->Times[$index];   // only include the result if we're uploading
        }
        try {
            $Guz = new \GuzzleHttp\Client();
            $response = $Guz->post('https://genericserver.link/mothership', [ 'form_params' => $data ]);
            if ($response->getStatusCode() == self::HTTP_OK) {
                try {
                    $consolidated = json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);
                    if (is_array($consolidated) && isset($consolidated['OK'])) {
                        return $consolidated;
                    }
                    return ['OK' => false, 'Error' => 'Data format error'];
                } catch (\Exception $ex) {
                    return ['OK' => false, 'Error' => 'Data decode error ' . $ex->getMessage()];
                }
            } else {
                return [ 'OK' => false, 'Error' => 'HTTP error ' . $response->getReasonPhrase() ];
            }
        } catch (\Exception $ex) {
            return ['OK' => false, 'Error' => 'Data upload error ' . $ex->getMessage()];
        }
    }
}
