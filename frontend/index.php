<?php

const ITERATIONS = 10;
const PRECISION = 5;
const SERIES_NAMES = ['On-page looping', 'Class-based looping', 'External data source'];

require_once '/var/www/vendor/autoload.php';

include_once 'classes.php';
include_once 'functions.php';
include_once 'ways.php';

// We do NOT use a Template builder
// This is because we want to keep flushing the output after each test type

echo <<< PAGE_TOP
<!DOCTYPE html>
<html lang="en-gb">
<head>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <link rel="stylesheet"
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh"
    crossorigin="anonymous">

</head>
<body>
<div class="container">
<div class="jumbotron">
<h1>PHP Performance tester</h1>
<p>The purpose of this code is to see how fast (relative to each other)
different ways of “doing something” are in PHP. The code is deliberately ultra-lightweight,
with no frameworks.</p>
<p>The “something” is generating a set of random numbers, and summing them.</p>
</div>
<div class="container">
<div class='alert alert-success'>
    Pre-seeding the various data structures, so that all we are seeing is the output times...
</div>

PAGE_TOP;
flush();

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
$displayIterations = number_format($Iterations);
$myclass = new MHL\Demo($Iterations);

echo "<div class='alert alert-success'>Running $displayIterations iterations of each loop.</div>";
flush();

echo '<h2>Results</h2>';

echo '<table class="table table-striped" id="resultsTable">
    <thead><th>Method</th><th>Time</th><th>Factor</th></thead>
    <tbody>';

$times = [];
$series = array_fill(0, count(SERIES_NAMES), array_fill(0, count(Ways(null, 'Index')), 0));
// Loop over our configured ways. There's a bit of code duplication here, to
// try and ensure that only what we're measuring is inside the timing loop,
// not additional structural conditionals.
foreach (Ways() as $index => $way) {
    if ($index == 0) {
        // handle the basic in-page loop separately
        $starttime = microtime(true);
        for ($i = $n = 0; $i < $Iterations; ++$i) {
            $n += rand();
        }
        $times[$index] = microtime(true) - $starttime;
    } elseif ($way['Loop']) {
        if ($way['Class']) {
            $starttime = microtime(true);
            for ($i = $n = 0; $i < $Iterations; ++$i) {
                $n += call_user_func([$myclass, $way['Function']], 1);
            }
            $times[$index] = microtime(true) - $starttime;
        } else {
            $starttime = microtime(true);
            for ($i = $n = 0; $i < $Iterations; ++$i) {
                $n += call_user_func($way['Function'], 1);
            }
            $times[$index] = microtime(true) - $starttime;
        }
    } else {
        if ($way['Class']) {
            $starttime = microtime(true);
            $n += call_user_func([$myclass, $way['Function']], $Iterations);
            $times[$index] = microtime(true) - $starttime;
        } else {
            $starttime = microtime(true);
            $n += call_user_func($way['Function'], $Iterations);
            $times[$index] = microtime(true) - $starttime;
        }
    }
    showResultRow($way['Table'], $times[$index], $times[0]);
    $series[$way['Series']][$index] = $times[$index];
}

echo <<< FORM_TOP
</tbody></table>

<h2>Run again</h2>
<form method='post'>
  <p>
    <label for="I">Number of iterations</label><br>
    <input type="text" id="fname" name="I" value="$Iterations">
    <input type="submit" name="submit" class='btn btn-primary'>
  </p>

  <p id='axisChoice' style='text-align: center;'>
    Axis type:

FORM_TOP;

if (empty($_REQUEST['axis']) || ($_REQUEST['axis'] != 'lin')) { // log axis, default
    echo "    <input type='hidden' id='axis' name='axis' value='log'>\n";
    echo "    <button type='button' class='btn btn-primary active' id='logaxis' data-axis='log'> ";
    echo "Logarithmic</button>\n";
    echo "    <button type='button' class='btn btn-primary' id='linaxis' data-axis='lin'> ";
    echo "Linear</button>\n";
} else {
    echo "    <input type='hidden' id='axis' name='axis' value='lin'>\n";
    echo "    <button type='button' class='btn btn-primary' id='logaxis' data-axis='log'> ";
    echo "Logarithmic</button>\n";
    echo "    <button type='button' class='btn btn-primary active' id='linaxis' data-axis='lin'>";
    echo " Linear</button>\n";
}

echo <<< SCRIPT_TOP
  </p>
</form>

<div id='container' style='width:100%; height:400px;'></div>
<script>
var myChart;
$(function() {
    myChart = Highcharts.chart('container', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Timings'
        },
        subtitle: {
            text: ($('#axis').val() == 'log') ? 'Logarithmic axis' : 'Linear axis'
        },
        xAxis: {
            categories: [

SCRIPT_TOP;
foreach (Ways(null, 'Graph') as $caption) {
    echo "                '$caption',\n";
}
echo "            ]\n        },\n";

$json = [];
foreach (SERIES_NAMES as $i => $name) {
    $json[] = ['name' => $name, 'data' => $series[$i]];
}
echo '        series: ', json_encode($json, JSON_PRETTY_PRINT), ",\n";

echo <<< PAGE_END
        yAxis: {
            type: ($('#axis').val() == 'log') ? 'logarithmic' : 'linear',
            minorTickInterval: 'auto',
            title: {text: 'Time for $displayIterations iterations (s)'}
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: false
                }
            }
        },
        tooltip: {
            enabled: true,
            formatter: function() {
                var txt = '<span style="font-size: 12px"><b>';
                txt += this.point.category + '</b></span><br/>';
                if (this.point.y >= 1) {
                    txt += Highcharts.numberFormat(this.point.y, 3) + ' s';
                } else if (this.point.y >= 0.001) {
                    txt += Highcharts.numberFormat(1000 * this.point.y, 3) + ' ms';
                } else {
                    txt += Highcharts.numberFormat(1e6 * this.point.y, 3) + ' μs';
                }
                return txt;
            }
        }
    });

    $('#logaxis, #linaxis').click(function() {
        $('#axis').val($(this).data('axis'));
        $('#axisChoice button').removeClass('active');
        $(this).addClass('active');
        myChart.setTitle(null, {text: ($('#axis').val() == 'log') ? 'Logarithmic axis' : 'Linear axis'}, false);
        myChart.yAxis[0].update({ type: ($('#axis').val() == 'log') ? 'logarithmic' : 'linear' }, false);
        myChart.redraw();
    });
});
</script>
</div>
</div>
</body>
</html>

PAGE_END;
