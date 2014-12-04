<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visspectrumualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    */
    global $path, $embed;
?>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/visspectrum/visualisations/visspectrum.helper.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="<?php echo $path;?>lib/highcharts/highstock.js"></script>
<script src="<?php echo $path;?>lib/highcharts/exporting.js"></script>
<script src="<?php echo $path;?>lib/highcharts/export-csv.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>

<div id="container" style="width:100%; height:400px; position:relative;"></div>

<button id="getcsv">Alert CSV</button>

<div id="graph_buttons" style="width:100%; height:18px; position:relative;">
    <div class='btn-group'>
        <button id='zoomin' class='btn' >+</button>
        <button id='zoomout' class='btn' >-</button>
        <button id='left' class='btn' ><</button>
        <button id='right' class='btn' >></button>
    </div>
</div>

<div id="mensajes" style="width:100%; height:100px;"></div>
<div id="mensajes2" style="width:100%; height:100px;"></div>



<script id="source" language="javascript" type="text/javascript">
    console.log(urlParams);
var feedspectrumname = "<?php echo $feedspectrumidname; ?>";
var path = "<?php echo $path; ?>";
var apikey = "<?php echo $apikey; ?>";
var embed = <?php echo $embed; ?>;
var valid = "<?php echo $valid; ?>";
var feedspectrumid = urlParams.feedspectrumid;
var interval = urlParams.interval;
    if (interval==undefined || interval=='') interval = 3600*24;
var plotColour = urlParams.colour;
    if (plotColour==undefined || plotColour=='') plotColour = "EDC240";
var units = urlParams.units;
    if (units==undefined || units=='') units = "";
var dp = urlParams.dp;
    if (dp==undefined || dp=='') dp = 2;
var scale = urlParams.scale;
    if (scale==undefined || scale=='') scale = 1;
var fill = +urlParams.fill;
    if (fill==undefined || fill=='') fill = 0;
if (fill>0) fill = true;
// Some browsers want the colour codes to be prepended with a "#". Therefore, we
// add one if it's not already there

//var timeWindow = (3600000*24.0*7);
var timeWindow = (3600000*4.0*1);
 view.start = +new Date - timeWindow;
 view.end = +new Date;

    var dat = [];
    var chart = $('#container').highcharts();
    var fecha=(new Date(view.start)).format("ddd, mmm dS, yyyy HH.MM:ss","UTC");
    var fecha_fin=(new Date(view.end)).format("ddd, mmm dS, yyyy HH.MM:ss","UTC");

$(function() {

    draw();
    create_chart();
    actualiza_rangos();

    function actualiza_rangos(){
         fecha=(new Date(view.start)).format("ddd, mmm dS, yyyy HH.MM:ss","UTC");
         fecha_fin=(new Date(view.end)).format("ddd, mmm dS, yyyy HH.MM:ss","UTC");
        $("#mensajes2").html("<br>Datos view2 desde start: "+fecha+"<br>Datos view2 hasta end: "+fecha_fin+"<br>");
        $("#mensajes2").show();

        //if (embed==false) {
         fecha=(new Date(dat[0][0])).format("ddd, mmm dS, yyyy HH.MM:ss","UTC");
         fin=dat.length-1;
        fecha_fin=(new Date(dat[fin][0])).format("ddd, mmm dS, yyyy HH.MM:ss","UTC");
        $("#mensajes").html("<br>Graph : "+feedspectrumname+" Interval:"+Math.round(interval)+
        "<br>Datos desde start: "+fecha+"<br>Datos hasta end: "+fecha_fin+"<br>");
        $("#mensajes").show();
        //alert(view.start);

    }

    function afterSetExtremes(e) {
        view.end=Math.round(e.max);
        view.start=Math.round(e.min);
        draw();
        var chart2 = $('#container').highcharts();
        chart2.showLoading('Loading data from server...');
        chart2.redraw();
        chart2.hideLoading();
        actualiza_rangos();
    }

    $("#zoomout").click(function () {view.zoomout();draw();create_chart();actualiza_rangos();});
    $("#zoomin").click(function () {view.zoomin(); draw();create_chart();actualiza_rangos();});
    $('#right').click(function () {view.panright(); draw();create_chart();actualiza_rangos();});
    $('#left').click(function () {view.panleft(); draw();create_chart();actualiza_rangos();});

        // create the chart
    function create_chart() {
        $('#container').highcharts('StockChart', {
         //chart = new Highcharts.StockChart({
            chart: {
                type: 'area',
                zoomType: 'xy',
                renderTo: 'container'
            },

            navigator: {
                adaptToUpdatedData: false,
                series: {
                    data: dat,
                    includeInCSVExport: false
                }
            },

            scrollbar: {
                liveRedraw: false
            },

            title: {
                text: 'Graph:' + feedspectrumname + " -->ID=" + feedspectrumid
            },

            subtitle: {
                text: 'From: '+ fecha+' To: '+fecha_fin
            },

            rangeSelector: {
                buttons: [{
                    type: 'hour',
                    count: 1,
                    text: '1h'
                }, {
                    type: 'day',
                    count: 1,
                    text: '1d'
                }, {
                    type: 'month',
                    count: 1,
                    text: '1m'
                }, {
                    type: 'year',
                    count: 1,
                    text: '1y'
                }, {
                    type: 'all',
                    text: 'All'
                }],
                inputEnabled: true, // it supports only days
                selected: 4 // all
            },

            xAxis: {
                events: {afterSetExtremes: afterSetExtremes},
                ordinal: false
                //,
                // minRange: 1 * 1000 // one hour//3600 * 1000 // one hour
            },

            series: [{
                name: "Current:",
                data: dat,
                dataGrouping: {
                    enabled: false
                },
                lineWidth: 1,
                marker: {
                    enabled: true,
                    radius: 2
                },
                tooltip: {
                    valueDecimals: 2,
                    valueSuffix: units//'[A]'
                }
            }]
        });
    }
//fin de highchart

    function draw()
    {
        dat = [];
        var npoints = 50000;
        //alert(view.start);
        //$("#mensajes").html("<br><h2>start: "+view.start+"<h2>");
        //interval = Math.round(((view.end - view.start)/npoints)/1000);
        interval = ((view.end - view.start)/npoints)/1000;
        //interval=0;
       //alert("iddraw="+feedspectrumid+"&start="+view.start+"&end="+view.end+"&interval="+interval);
        $.ajax({
            url: path+'feedspectrum/average.json',
            data: "id="+feedspectrumid+"&start="+view.start+"&end="+view.end+"&interval="+interval,
            dataType: 'json',
            async: false,
            success: function(data_in) { dat = data_in; }
        });
        var salida = [];
        var delta=0;
        for (var z=0; z<dat.length; z++) {
            var tiempo = dat[z][0];
            var t_ini=tiempo;
            var array_V = dat [z][2];
//alert (array_V.toString());
            for (var i = 0; i < array_V.length-1; i++) {
                delta= dat[z][2][i+1]-dat[z][2][i];
                tiempo=tiempo+delta*1.0;
                salida.push([tiempo, dat[z][3][i]]);
            }
            var t_fin=tiempo;
        }
        dat = salida;
        out = [];
        if (scale!=1) {
            for (var z=0; z<dat.length; z++) {
                var val = dat[z][1] * scale;
                out.push([dat[z][0],val]);
            }
            dat = out;
        }
    }
    // fin de draw
    $('#getcsv').click(function () {
        alert(chart.getCSV());
    });

});
</script>

