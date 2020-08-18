<?php

const ITERATIONS = 10;
const SERIES_NAMES = ['On-page looping', 'Class-based looping', 'External data source'];

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
  <style type='text/css'>
    .left   {text-align:left;}
    .right  {text-align:right;}
    .centre {text-align:center;}
    .hid    {display:none;}
    .spinner{width:20px;height:20px;}
  </style>
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
<div class='alert alert-success' id='seeding'>
    Pre-seeding the various data structures, so that all we are seeing is the output times...
</div>

PAGE_TOP;
flush();

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
$displayIterations = number_format($Iterations);

echo "<div class='alert alert-success hid' id='running'>Running $displayIterations iterations of each loop.</div>";
flush();

echo '<h2>Results</h2>';

echo '<table class="table table-striped" id="resultsTable">
    <thead><th>Method</th><th class="right">Time</th><th class="right">Factor</th></thead>
    <tbody>';

foreach (Ways() as $index => $way) {
    echo "<tr><td>$way[Table]</td>\n";
    echo "<td class='centre' id='Time_$index'><img class='spinner' src='spinner.svg'></td>\n";
    echo "<td class='centre' id='Factor_$index'><img class='spinner' src='spinner.svg'></td></tr>\n";
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
    $json[] = ['name' => $name, 'data' => array_fill(0, count(Ways(null, 'Index')), 0)];
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

    // And do some work...
    $.post('ajax.php', 'Setup=$Iterations', function(ajaxdata) {
        $('#seeding, #running').toggle();
        RunWay(0);
    });
});

function RunWay(index)
{
    $.post('ajax.php', 'Way=' + index, function(ajaxdata) {
        if ('index' in ajaxdata) {
            $('#Time_' + index).html(ajaxdata.time).removeClass('centre').addClass('right');
            $('#Factor_' + index).html(ajaxdata.factor).removeClass('centre').addClass('right');
            myChart.series[ajaxdata.series].setData(ajaxdata.points);
            RunWay(++index);
        } else {
            $.post('ajax.php', 'Teardown=Y');
            $('#running').hide();
        }
    });
}
</script>
</div>
</div>
</body>
</html>

PAGE_END;
