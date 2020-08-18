<?php

const ITERATIONS = 10;
const SERIES_NAMES = ['On-page looping', 'Class-based looping', 'External data source'];

require '/var/www/vendor/autoload.php';
include_once 'ways.php';


$mloader = new Mustache_Loader_FilesystemLoader($_SERVER['DOCUMENT_ROOT'].'/templates');
$mustache = new Mustache_Engine(['loader' => $mloader]);
$mustachedata = [];

$Iterations = (empty($_REQUEST['I']) || !is_numeric($_REQUEST['I'])) ? ITERATIONS : (int)$_REQUEST['I'];
$displayIterations = number_format($Iterations);
$data["displayIterations"] = $displayIterations;
echo $mustache->render("top", $data);
flush();

$data["ways"] = [];
foreach (Ways() as $index => $way) {
    $thisway = [];
    $thisway["index"] = $index;
    $thisway["description"] = $way["Table"];
    $data["ways"][] = $thisway;
}

echo "<hr/><pre>";
print_r ($data);
echo "</pre>";


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
