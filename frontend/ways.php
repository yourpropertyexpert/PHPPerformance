<?php

function Ways($index = null, $element = null)
{
    // Our configuration. For each "way", we have:
    //  Table : the "long" caption for the results table
    //  Graph : the "short" caption, for the graph
    //  Class : a boolean (for now, anyway), true if this is a class method
    //  Function : the function/method to be invoked
    //  Loop : boolean true if the caller needs to do the looping
    //  CheckTotal : boolean true to check the total against the first way
    //  Series : the number of the series (0..3) under which this way appears in the charts
    $Ways = [ [ 'Table' => 'Page: Simple loop',
                'Graph' => 'Simple loop',
                'Class' => false,
                'Function' => null,
                'Loop' => true,
                'CheckTotal' => false,
                'Series' => 0,
              ],
              [ 'Table' => 'Page: Local function doing the iteration internally',
                'Graph' => 'Local function called once',
                'Class' => false,
                'Function' => 'loopMeParameterised',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 0,
              ],
              [ 'Table' => 'Page: Local function called multiple times',
                'Graph' => 'Local function called per iteration',
                'Class' => false,
                'Function' => 'loopMeParameterised',
                'Loop' => true,
                'CheckTotal' => true,
                'Series' => 0,
              ],
              [ 'Table' => 'Class: Null function called once (to assess overhead of just calling)',
                'Graph' => 'Class null function (single call)',
                'Class' => true,
                'Function' => 'getNull',
                'Loop' => false,
                'CheckTotal' => false,
                'Series' => 1,
              ],
              [ 'Table' => 'Class: Null function called n times (to assess overhead of just calling)',
                'Graph' => 'Class null function (n calls)',
                'Class' => true,
                'Function' => 'getNull',
                'Loop' => true,
                'CheckTotal' => false,
                'Series' => 1,
              ],
              [ 'Table' => 'Class: Single method call that did the iteration in the method',
                'Graph' => 'Loop inside a single method call',
                'Class' => true,
                'Function' => 'getN',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 2,
              ],
              [ 'Table' => 'Class: Call the method multiple times from a loop in the calling page',
                'Graph' => 'Method called once per iteration',
                'Class' => true,
                'Function' => 'getN',
                'Loop' => true,
                'CheckTotal' => true,
                'Series' => 2,
              ],
              [ 'Table' => 'External: Calling memcached n times - on a unix socket each time',
                'Graph' => 'Memcached (local: unix socket)',
                'Class' => true,
                'Function' => 'getNFromLocalMemcached',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Calling memcached n times - on a UDP port each time',
                'Graph' => 'Memcached (container: UDP)',
                'Class' => true,
                'Function' => 'getNFromMemcached',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, that ran a loop calling class shared Redis each time',
                'Graph' => 'Redis',
                'Class' => true,
                'Function' => 'getNFromRedis',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, n MySQL queries (not prepared statment)',
                'Graph' => 'MySQL (n queries)',
                'Class' => true,
                'Function' => 'getNFromDBQuery',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, n MySQL queries against same prepared statement',
                'Graph' => 'MySQL (n queries, prepared statements)',
                'Class' => true,
                'Function' => 'getNFromDBQueryPrepared',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, that ran one MySQL query then looped over the returned data',
                'Graph' => 'MySQL (one query)',
                'Class' => true,
                'Function' => 'getNFromDBQueryInOneGo',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Run a loop calling a new SQLite query each time',
                'Graph' => 'SQLite (n queries)',
                'Class' => true,
                'Function' => 'getNFromSQLite',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Make a single SQLite query and then unpack the results',
                'Graph' => 'SQLite (1 query)',
                'Class' => true,
                'Function' => 'getNFromSQLiteInOneGo',
                'Loop' => false,
                'CheckTotal' => true,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Run a loop calling a shared API using cURL each time',
                'Graph' => 'API (cURL)',
                'Class' => true,
                'Function' => 'getNFromAPIcURL',
                'Loop' => false,
                'CheckTotal' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Run a loop calling a shared API using Guzzle each time',
                'Graph' => 'API (Guzzle)',
                'Class' => true,
                'Function' => 'getNFromAPIGuzzle',
                'Loop' => false,
                'CheckTotal' => false,
                'Series' => 3,
              ],
            ];

    if (is_null($index)) {
        // If we're not given an index, we return an array
        if (is_null($element)) {
            // Not given an element either, just return everything!
            return $Ways;
        }

        if ($element == 'Index') {
            // an element of 'Index' or 'IndexTable' are fakes
            return array_keys($Ways);
        }
        if ($element == 'IndexTable') {
            $ret = [];
            foreach ($Ways as $index => $way) {
                $ret[] = ['index' => $index, 'description' => $way['Table']];
            }
            return $ret;
        }

        return array_map(function ($x) use ($element) {
            return $x[$element] ?? null;
        }, $Ways);
    }

    // We've got an index. If it's numeric, we want that one; if it's a
    // string, it should match one of the captions
    if (!is_numeric($index)) {
        foreach ($Ways as $i => $way) {
            if (($way['Table'] == $index) || ($way['Graph'] == $index)) {
                $index = $i;
                break;
            }
        }
    }

    if (!is_numeric($index) || !isset($Ways[$index])) {
        throw new Exception("Cannot find way '$index'");
    }

    if (is_null($element)) {
        return $Ways[$index];
    }

    if ($element == 'Index') {
        // No point in faking "IndexTable" for a single value
        return $index;
    }

    if (array_key_exists($element, $Ways[$index])) {
        return $Ways[$index][$element];
    }

    throw new Exception("'$element' is not a known parameter for ways");
}
