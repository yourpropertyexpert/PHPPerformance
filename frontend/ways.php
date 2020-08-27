<?php

function Ways($index = null, $element = null)
{
    // Our configuration. For each "way", we have:
    //  Table : the "long" caption for the results table
    //  Graph : the "short" caption, for the graph
    //  Class : a boolean (for now, anyway), true if this is a class method
    //  Function : the function/method to be invoked
    //  Loop : boolean true if the caller needs to do the looping
    //  Series : the number of the series (0..2) this way appears in the
    //      charts under
    $Ways = [ [ 'Table' => 'Page: Simple loop',
                'Graph' => 'Simple loop',
                'Class' => false,
                'Function' => null,
                'Loop' => true,
                'Series' => 0,
              ],
              [ 'Table' => 'Page: Local function doing the iteration internally',
                'Graph' => 'Local function called once',
                'Class' => false,
                'Function' => 'loopMeParameterised',
                'Loop' => false,
                'Series' => 0,
              ],
              [ 'Table' => 'Page: Local function called multiple times',
                'Graph' => 'Local function called per iteration',
                'Class' => false,
                'Function' => 'loopMeParameterised',
                'Loop' => true,
                'Series' => 0,
              ],
              [ 'Table' => 'Class: Null function called once (to assess overhead of just calling)',
                'Graph' => 'Class null function (single call)',
                'Class' => true,
                'Function' => 'getNull',
                'Loop' => false,
                'Series' => 1,
              ],
              [ 'Table' => 'Class: Null function called n times (to assess overhead of just calling)',
                'Graph' => 'Class null function (n calls)',
                'Class' => true,
                'Function' => 'getNull',
                'Loop' => true,
                'Series' => 1,
              ],
              [ 'Table' => 'Class: Single method call that did the iteration in the method',
                'Graph' => 'Loop inside a single method call',
                'Class' => true,
                'Function' => 'getN',
                'Loop' => false,
                'Series' => 2,
              ],
              [ 'Table' => 'Class: Call the method multiple times from a loop in the calling page',
                'Graph' => 'Method called once per iteration',
                'Class' => true,
                'Function' => 'getN',
                'Loop' => true,
                'Series' => 2,
              ],
              [ 'Table' => 'External: Single method call, that ran a loop calling local memcached on a unix socket each time',
                'Graph' => 'Memcached (on web container)',
                'Class' => true,
                'Function' => 'getNFromLocalMemcached',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, that ran a loop calling a shared memcached over UDP each time',
                'Graph' => 'Memcached (on separate docker container)',
                'Class' => true,
                'Function' => 'getNFromMemcached',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, that ran a loop calling class shared Redis each time',
                'Graph' => 'Redis',
                'Class' => true,
                'Function' => 'getNFromRedis',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, n MySQL queries (not prepared statment)',
                'Graph' => 'MySQL (n queries)',
                'Class' => true,
                'Function' => 'getNFromDBQuery',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, n MySQL queries against same prepared statement',
                'Graph' => 'MySQL (n queries, prepared statements)',
                'Class' => true,
                'Function' => 'getNFromDBQueryPrepared',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, that ran one MySQL query then looped over the returned data',
                'Graph' => 'MySQL (one query)',
                'Class' => true,
                'Function' => 'getNFromDBQueryInOneGo',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Run a loop calling a new SQLite query each time',
                'Graph' => 'SQLite (n queries)',
                'Class' => true,
                'Function' => 'getNFromSQLite',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Make a single SQLite query and then unpack the results',
                'Graph' => 'SQLite (1 query)',
                'Class' => true,
                'Function' => 'getNFromSQLiteInOneGo',
                'Loop' => false,
                'Series' => 3,
              ],
              [ 'Table' => 'External: Single method call, that ran a loop calling class shared API each time',
                'Graph' => 'API',
                'Class' => true,
                'Function' => 'getNFromAPI',
                'Loop' => false,
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
