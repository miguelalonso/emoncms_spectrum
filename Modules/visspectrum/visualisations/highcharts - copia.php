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
//echo "path $path";
?>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/visspectrum/visualisations/visspectrum.helper.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="<?php echo $path;?>lib/highcharts/highstock.js"></script>
<script src="<?php echo $path;?>lib/highcharts/exporting.js"></script>
<script src="<?php echo $path;?>lib/highcharts/export-csv.js"></script>

<div id="container" style="width:100%; height:400px;"></div>


<button id="getcsv">Alert CSV</button>



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

var timeWindow = (3600000*24.0*7);
view.start = +new Date - timeWindow;
view.end = +new Date;

var dat = [];

$(function() {
    draw();

    var chart = new Highcharts.StockChart({

        chart: {
            renderTo: 'container',
            zoomType:'xy'
        },
        rangeSelector: {

            buttons: [{
                type: 'day',
                count: 3,
                text: '3d'
            }, {
                type: 'week',
                count: 1,
                text: '1w'
            }, {
                type: 'month',
                count: 1,
                text: '1m'
            }, {
                type: 'month',
                count: 6,
                text: '6m'
            }, {
                type: 'year',
                count: 1,
                text: '1y'
            }, {
                type: 'all',
                text: 'All'
            }],
            selected: 3
        },

        yAxis: {
            title: {
                text: 'Temperature (°C)'
            }
        },

        title: {
            text: 'Título del gráfico'
        },

        navigator: {
            series: {
                includeInCSVExport: false
            }
        },


        series: [{
            name:"Datos Serie1",
            type :'area',
            threshold : null,
            fillColor : {
                linearGradient: {
                    x1: 0,
                    y1: 0,
                    x2: 0,
                    y2: 1
                },
                stops: [
                    [0, Highcharts.getOptions().colors[0]],
                    [1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                ]
            },
            lineWidth : 1,
            marker : {
                enabled : true,
                radius : 2
            },
            tooltip: {
                valueDecimals: 1,
                valueSuffix: '°C'
            },

            //data: [29.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4],
            data: dat
            //pointStart:  Date.UTC(2013, 0, 1,12,1,1,0),
            //pointInterval: 1//24 * 36e5
        }/*,
           {
                name:"Datos Serie2",
                data: [19.9, 51.5, 136.4, 129.2, 164.0, 176.0, 155.6, 148.5, 296.4, 134.1, 55.6, 44.4],
                pointStart: Date.UTC(2014, 0, 1),
                pointInterval: 48 * 36e5
            }*/],

        exporting: {
            csv: {
                dateFormat: '%Y-%m-%d %H:%M:%S.%L' //con milisegundos %L
            }
        }

    });
//fin de highchart

    if (embed==false) {
        $("#visspectrum-title").html("<br><h2>Graph: "+feedspectrumname+"<h2>");
        $("#info").show();
    }

    

    function draw()
    {
        dat = [];
        var npoints = 800;
        interval = Math.round(((view.end - view.start)/npoints)/1000);
       // alert("id="+feedspectrumid+"&start="+view.start+"&end="+view.end+"&interval="+interval);
        $.ajax({
            url: path+'feedspectrum/average.json',
            data: "id="+feedspectrumid+"&start="+view.start+"&end="+view.end+"&interval="+interval,
            dataType: 'json',
            async: false,
            success: function(data_in) { dat = data_in; }
        });

//alert ("salida");
         //datos=dat[0][0][0];
         //alert(dat.toString());
//ahora reordenamos los datos para poner el tiempo y los valores de I
// en el tiempo están los valores de X separados 1 ms
//[[1416504166000,
// "numero de serie, tab   por ejemplo",
// [1.1,2.2,3.3,4.4,5.5],
// [6.6,7.7,8.8,9.9,10.1]],
// [1416504255000,"numero de serie, tab   por ejemplo",[1.1,2.2,3.3,4.4,5.5],[6.6,7.7,8.8,9.9,10.1]],
        var salida = [];
        var delta=0;
        for (var z=0; z<dat.length; z++) {
            var tiempo = dat[z][0];
            var t_ini=tiempo;
            var array_V = dat [z][2];

            for (var i = 0; i < array_V.length; i++) {
                delta= Math.abs(dat[z][2][i+1]-dat[z][2][i]);
                if(delta>0) tiempo=tiempo+delta;
                salida.push([tiempo, dat[z][3][i]]);
            }
            var t_fin=tiempo;
        }
        dat = salida;
     //   datos=dat[0][0];
      //  alert(datos);
        //alert(t_fin);

        out = [];
        if (scale!=1) {
            for (var z=0; z<dat.length; z++) {
                var val = dat[z][1] * scale;
                out.push([dat[z][0],val]);
            }
            dat = out;
        }
              // alert(dat.toString());

    }
    

    $('#getcsv').click(function () {
        alert(chart.getCSV());
    });

});
</script>

